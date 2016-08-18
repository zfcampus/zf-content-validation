<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentValidation;

use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\ListenerAggregateTrait;
use Zend\Http\Request as HttpRequest;
use Zend\Http\Response;
use Zend\InputFilter\CollectionInputFilter;
use Zend\InputFilter\Exception\InvalidArgumentException as InputFilterInvalidArgumentException;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\UnknownInputsCapableInterface;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch as V2RouteMatch;
use Zend\Router\RouteMatch;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Stdlib\ArrayUtils;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;
use ZF\ContentNegotiation\ParameterDataContainer;

class ContentValidationListener implements ListenerAggregateInterface, EventManagerAwareInterface
{
    use ListenerAggregateTrait;

    const EVENT_BEFORE_VALIDATE = 'contentvalidation.beforevalidate';

    /**
     * @var array
     */
    protected $config = [];

    protected $events;

    /**
     * @var ServiceLocatorInterface
     */
    protected $inputFilterManager;

    /**
     * Cache of input filter service names/instances
     *
     * @var array
     */
    protected $inputFilters = [];

    /**
     * @var array
     */
    protected $methodsWithoutBodies = [
        'GET',
        'HEAD',
        'OPTIONS',
        'DELETE',
    ];

    /**
     * Map of REST controllers => route identifier names
     *
     * Used to determine if we have a collection or an entity, for purposes of validation.
     *
     * @var array
     */
    protected $restControllers;

    /**
     * @param array $config
     * @param null|ServiceLocatorInterface $inputFilterManager
     * @param array $restControllers
     */
    public function __construct(
        array $config = [],
        ServiceLocatorInterface $inputFilterManager = null,
        array $restControllers = []
    ) {
        $this->config               = $config;
        $this->inputFilterManager   = $inputFilterManager;
        $this->restControllers      = $restControllers;

        if (isset($config['methods_without_bodies']) && is_array($config['methods_without_bodies'])) {
            foreach ($config['methods_without_bodies'] as $method) {
                $this->addMethodWithoutBody($method);
            }
        }
    }

    /**
     * Set event manager instance
     *
     * Sets the event manager identifiers to the current class, this class, and
     * the resource interface.
     *
     * @param  EventManagerInterface $events
     * @return ContentValidationListener
     */
    public function setEventManager(EventManagerInterface $events)
    {
        $events->addIdentifiers([
            get_class($this),
            __CLASS__,
            self::EVENT_BEFORE_VALIDATE
        ]);
        $this->events = $events;

        return $this;
    }

    /**
     * Retrieve event manager
     *
     * Lazy-instantiates an EM instance if none provided.
     *
     * @return EventManagerInterface
     */
    public function getEventManager()
    {
        if (! $this->events) {
            $this->setEventManager(new EventManager());
        }
        return $this->events;
    }

    /**
     * @see   ListenerAggregateInterface
     * @param EventManagerInterface $events
     * @param int $priority
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        // trigger after authentication/authorization and content negotiation
        $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, [$this, 'onRoute'], -650);
    }

    /**
     * Attempt to validate the incoming request
     *
     * If an input filter is associated with the matched controller service,
     * attempt to validate the incoming request, and inject the event with the
     * input filter, as the "ZF\ContentValidation\InputFilter" parameter.
     *
     * Uses the ContentNegotiation ParameterDataContainer to retrieve parameters
     * to validate, and returns an ApiProblemResponse when validation fails.
     *
     * Also returns an ApiProblemResponse in cases of:
     *
     * - Invalid input filter service name
     * - Missing ParameterDataContainer (i.e., ContentNegotiation is not registered)
     *
     * @param MvcEvent $e
     * @return null|ApiProblemResponse
     */
    public function onRoute(MvcEvent $e)
    {
        $request = $e->getRequest();
        if (! $request instanceof HttpRequest) {
            return;
        }

        $routeMatches = $e->getRouteMatch();
        if (! ($routeMatches instanceof RouteMatch || $routeMatches instanceof V2RouteMatch)) {
            return;
        }

        $controllerService = $routeMatches->getParam('controller', false);
        if (! $controllerService) {
            return;
        }

        $method = $request->getMethod();
        $inputFilterService = $this->getInputFilterService($controllerService, $method);
        if (! $inputFilterService) {
            return;
        }

        if (! $this->hasInputFilter($inputFilterService)) {
            return new ApiProblemResponse(
                new ApiProblem(
                    500,
                    sprintf('Listed input filter "%s" does not exist; cannot validate request', $inputFilterService)
                )
            );
        }

        $dataContainer = $e->getParam('ZFContentNegotiationParameterData', false);
        if (! $dataContainer instanceof ParameterDataContainer) {
            return new ApiProblemResponse(
                new ApiProblem(
                    500,
                    'ZF\\ContentNegotiation module is not initialized; cannot validate request'
                )
            );
        }

        $data = in_array($method, $this->methodsWithoutBodies)
            ? $dataContainer->getQueryParams()
            : $dataContainer->getBodyParams();

        if (null === $data || '' === $data) {
            $data = [];
        }

        $isCollection = $this->isCollection($controllerService, $data, $routeMatches, $request);

        $files = $request->getFiles();
        if (! $isCollection && 0 < count($files)) {
            // File uploads are not validated for collections; impossible to
            // match file fields to discrete sets
            $data = ArrayUtils::merge($data, $files->toArray(), true);
        }

        $inputFilter = $this->getInputFilter($inputFilterService);

        if ($isCollection && ! in_array($method, $this->methodsWithoutBodies)) {
            $collectionInputFilter = new CollectionInputFilter();
            $collectionInputFilter->setInputFilter($inputFilter);
            $inputFilter = $collectionInputFilter;
        }

        $e->setParam('ZF\ContentValidation\InputFilter', $inputFilter);

        $currentEventName = $e->getName();
        $e->setName(self::EVENT_BEFORE_VALIDATE);

        $events = $this->getEventManager();
        $results = $events->triggerEventUntil(function ($result) {
            return ($result instanceof ApiProblem
                || $result instanceof ApiProblemResponse
            );
        }, $e);
        $e->setName($currentEventName);

        $last = $results->last();

        if ($last instanceof ApiProblem) {
            $last = new ApiProblemResponse($last);
        }

        if ($last instanceof ApiProblemResponse) {
            return $last;
        }

        $inputFilter->setData($data);

        $status = ($request->isPatch())
            ? $this->validatePatch($inputFilter, $data, $isCollection)
            : $inputFilter->isValid();

        if ($status instanceof ApiProblemResponse) {
            return $status;
        }

        // Invalid? Return a 422 response.
        if (false === $status) {
            return new ApiProblemResponse(
                new ApiProblem(422, 'Failed Validation', null, null, [
                    'validation_messages' => $inputFilter->getMessages(),
                ])
            );
        }

        // Should we use the raw data vs. the filtered data?
        // - If no `use_raw_data` flag is present, always use the raw data, as
        //   that was the default experience starting in 1.0.
        // - If the flag is present AND is boolean true, that is also
        //   an indicator that the raw data should be present.
        $useRawData = $this->useRawData($controllerService);
        if (! $useRawData) {
            $data = $inputFilter->getValues();
        }

        // If we don't have an instance of UnknownInputsCapableInterface, or no
        // unknown data is in the input filter, at this point we can just
        // set the current data into the data container.
        if (! $inputFilter instanceof UnknownInputsCapableInterface
            || ! $inputFilter->hasUnknown()
        ) {
            $dataContainer->setBodyParams($data);
            return;
        }

        $unknown    = $inputFilter->getUnknown();

        if ($this->allowsOnlyFieldsInFilter($controllerService)) {
            if ($inputFilter instanceof CollectionInputFilter) {
                $unknownFields = [];
                foreach ($unknown as $key => $fields) {
                    $unknownFields[] = '[' . $key . ': ' . implode(', ', array_keys($fields)) . ']';
                }
                $fields = implode(', ', $unknownFields);
            } else {
                $fields = implode(', ', array_keys($unknown));
            }
            $detail  = sprintf('Unrecognized fields: %s', $fields);
            $problem = new ApiProblem(Response::STATUS_CODE_422, $detail);

            return new ApiProblemResponse($problem);
        }

        // The raw data already contains unknown inputs, so no need to merge
        // them with the data.
        if ($useRawData) {
            $dataContainer->setBodyParams($data);
            return;
        }

        // When not using raw data, we merge the unknown data with the
        // validated data to get the full set of input.
        $dataContainer->setBodyParams(array_merge($data, $unknown));
    }

    /**
     * Add HTTP Method without body content
     *
     * @param string $method
     */
    public function addMethodWithoutBody($method)
    {
        $this->methodsWithoutBodies[] = $method;
    }

    /**
     * @param string $controllerService
     * @return boolean
     */
    protected function allowsOnlyFieldsInFilter($controllerService)
    {
        if (isset($this->config[$controllerService]['allows_only_fields_in_filter'])) {
            return true === $this->config[$controllerService]['allows_only_fields_in_filter'];
        }

        return false;
    }

    /**
     * @param $controllerService
     * @return bool
     */
    protected function useRawData($controllerService)
    {
        if (! isset($this->config[$controllerService]['use_raw_data'])
            || (isset($this->config[$controllerService]['use_raw_data'])
                && $this->config[$controllerService]['use_raw_data'] === true)
        ) {
            return true;
        }
        return false;
    }

    /**
     * Retrieve the input filter service name
     *
     * Test first to see if we have a method-specific input filter, and
     * secondarily for a general one.
     *
     * If neither are present, return boolean false.
     *
     * @param  string $controllerService
     * @param  string $method
     * @return string|false
     */
    protected function getInputFilterService($controllerService, $method)
    {
        if (isset($this->config[$controllerService][$method])) {
            return $this->config[$controllerService][$method];
        }

        if (in_array($method, $this->methodsWithoutBodies)) {
            return false;
        }

        if (isset($this->config[$controllerService]['input_filter'])) {
            return $this->config[$controllerService]['input_filter'];
        }

        return false;
    }

    /**
     * Determine if we have an input filter matching the service name
     *
     * @param string $inputFilterService
     * @return bool
     */
    protected function hasInputFilter($inputFilterService)
    {
        if (array_key_exists($inputFilterService, $this->inputFilters)) {
            return true;
        }

        if (! $this->inputFilterManager
            || ! $this->inputFilterManager->has($inputFilterService)
        ) {
            return false;
        }

        $inputFilter = $this->inputFilterManager->get($inputFilterService);
        if (! $inputFilter instanceof InputFilterInterface) {
            return false;
        }

        $this->inputFilters[$inputFilterService] = $inputFilter;
        return true;
    }

    /**
     * Retrieve the named input filter service
     *
     * @param string $inputFilterService
     * @return InputFilterInterface
     */
    protected function getInputFilter($inputFilterService)
    {
        return $this->inputFilters[$inputFilterService];
    }

    /**
     * Does the request represent a collection?
     *
     * @param string $serviceName
     * @param array $data
     * @param RouteMatch|V2RouteMatch $matches
     * @param HttpRequest $request
     * @return bool
     */
    protected function isCollection($serviceName, $data, $matches, HttpRequest $request)
    {
        if (! array_key_exists($serviceName, $this->restControllers)) {
            return false;
        }

        if ($request->isPost() && (empty($data) || ArrayUtils::isHashTable($data))) {
            return false;
        }

        $identifierName = $this->restControllers[$serviceName];
        if ($matches->getParam($identifierName) !== null) {
            return false;
        }

        return (null === $request->getQuery($identifierName, null));
    }

    /**
     * Validate a PATCH request
     *
     * @param InputFilterInterface $inputFilter
     * @param array|object $data
     * @param bool $isCollection
     * @return bool|ApiProblemResponse
     */
    protected function validatePatch(InputFilterInterface $inputFilter, $data, $isCollection)
    {
        if ($isCollection) {
            $validationGroup = $data;
            foreach ($validationGroup as &$subData) {
                $subData = array_keys($subData);
            }
        } else {
            $validationGroup = array_keys($data);
        }

        try {
            $inputFilter->setValidationGroup($validationGroup);
            return $inputFilter->isValid();
        } catch (InputFilterInvalidArgumentException $ex) {
            $pattern = '/expects a list of valid input names; "(?P<field>[^"]+)" was not found/';
            $matched = preg_match($pattern, $ex->getMessage(), $matches);
            if (! $matched) {
                return new ApiProblemResponse(
                    new ApiProblem(400, $ex)
                );
            }

            return new ApiProblemResponse(
                new ApiProblem(400, 'Unrecognized field "' . $matches['field'] . '"')
            );
        }
    }
}
