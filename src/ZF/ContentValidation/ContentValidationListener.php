<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentValidation;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\ListenerAggregateTrait;
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
    use ListenerAggregateTrait;

    /**
     * @var array
     */
    protected $config = [];

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
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * @param array $config
     * @param null|ServiceLocatorInterface $services
     */
    public function __construct(array $config = [], ServiceLocatorInterface $services = null)
    {
        $this->config   = $config;
        $this->services = $services;
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

        if (in_array($request->getMethod(), $this->methodsWithoutBodies)) {
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

        if (! isset($this->config[$controllerService]['input_filter'])) {
            return;
        }

        $inputFilterService = $this->config[$controllerService]['input_filter'];
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
            $data = [];
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
                    new ApiProblem(422, 'Unrecognized field "' . $matches['field'] . '"')
                );
            }
        }

        $inputFilter->setData($data);
        if ($inputFilter->isValid()) {
            return;
        }

        return new ApiProblemResponse(
            new ApiProblem(422, 'Failed Validation', null, null, [
                'validation_messages' => $inputFilter->getMessages(),
            ])
        );
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

        if (! $this->services
            || ! $this->services->has($inputFilterService)
        ) {
            return false;
        }

        $inputFilter = $this->services->get($inputFilterService);
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
