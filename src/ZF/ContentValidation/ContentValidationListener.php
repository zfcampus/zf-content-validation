<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentValidation;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\Http\Request as HttpRequest;
use Zend\InputFilter\Exception\InvalidArgumentException as InputFilterInvalidArgumentException;
use Zend\InputFilter\InputFilterInterface;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;
use ZF\ContentNegotiation\ParameterDataContainer;

class ContentValidationListener implements ListenerAggregateInterface
{
    /**
     * @var array
     */
    protected $config = array();

    /**
     * @var ServiceLocatorInterface
     */
    protected $inputFilterManager;

    /**
     * Cache of input filter service names/instances
     *
     * @var array
     */
    protected $inputFilters = array();

    /**
     * @var \Zend\Stdlib\CallbackHandler[]
     */
    protected $listeners = array();

    /**
     * @var array
     */
    protected $methodsWithoutBodies = array(
        'GET',
        'HEAD',
        'OPTIONS',
        'DELETE',
    );

    /**
     * @param array $config
     * @param null|ServiceLocatorInterface $inputFilterManager
     */
    public function __construct(array $config = array(), ServiceLocatorInterface $inputFilterManager = null)
    {
        $this->config             = $config;
        $this->inputFilterManager = $inputFilterManager;
    }

    /**
     * @see   ListenerAggregateInterface
     * @param EventManagerInterface $events
     * @param int $priority
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        // trigger after authentication/authorization and content negotiation
        $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, array($this, 'onRoute'), -650);
    }

    /**
     * @see   ListenerAggregateInterface
     * @param EventManagerInterface $events
     */
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $callback) {
            if ($events->detach($callback)) {
                unset($this->listeners[$index]);
            }
        }
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

        $method = $request->getMethod();
        if (in_array($method, $this->methodsWithoutBodies)) {
            return;
        }

        $routeMatches = $e->getRouteMatch();
        if (! $routeMatches instanceof RouteMatch) {
            return;
        }
        $controllerService = $routeMatches->getParam('controller', false);
        if (! $controllerService) {
            return;
        }

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
        $data = $dataContainer->getBodyParams();
        if (null === $data || '' === $data) {
            $data = array();
        }

        $inputFilter = $this->getInputFilter($inputFilterService);
        $e->setParam('ZF\ContentValidation\InputFilter', $inputFilter);

        if ($request->isPatch()) {
            try {
                $inputFilter->setValidationGroup(array_keys($data));
            } catch (InputFilterInvalidArgumentException $ex) {
                $pattern = '/expects a list of valid input names; "(?P<field>[^"]+)" was not found/';
                $matched = preg_match($pattern, $ex->getMessage(), $matches);
                if (!$matched) {
                    return new ApiProblemResponse(
                        new ApiProblem(400, $ex)
                    );
                }

                return new ApiProblemResponse(
                    new ApiProblem(400, 'Unrecognized field "' . $matches['field'] . '"')
                );
            }
        }

        $inputFilter->setData($data);
        if ($inputFilter->isValid()) {
            return;
        }

        return new ApiProblemResponse(
            new ApiProblem(422, 'Failed Validation', null, null, array(
                'validation_messages' => $inputFilter->getMessages(),
            ))
        );
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
}
