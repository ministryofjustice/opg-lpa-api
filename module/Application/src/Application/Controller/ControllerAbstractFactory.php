<?php

namespace Application\Controller;

use Application\Controller\Version2\AbstractController;
use Application\Controller\Version2\ApplicationController;
use Application\Controller\Version2\CertificateProviderController;
use Application\Controller\Version2\CorrespondentController;
use Application\Controller\Version2\DonorController;
use Application\Controller\Version2\InstructionController;
use Application\Controller\Version2\LockController;
use Application\Controller\Version2\NotifiedPeopleController;
use Application\Controller\Version2\PaymentController;
use Application\Controller\Version2\PdfController;
use Application\Controller\Version2\PreferenceController;
use Application\Controller\Version2\PrimaryAttorneyController;
use Application\Controller\Version2\PrimaryAttorneyDecisionsController;
use Application\Controller\Version2\RepeatCaseNumberController;
use Application\Controller\Version2\ReplacementAttorneyController;
use Application\Controller\Version2\ReplacementAttorneyDecisionsController;
use Application\Controller\Version2\SeedController;
use Application\Controller\Version2\TypeController;
use Application\Controller\Version2\UserController;
use Application\Controller\Version2\WhoAreYouController;
use Application\Controller\Version2\WhoIsRegisteringController;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\Stdlib\DispatchableInterface as Dispatchable;
use Exception;
use RuntimeException;

/**
 * Creates a controller based on those requested without a specific entry in the controller service locator.
 *
 * Class ControllerAbstractFactory
 * @package Application\ControllerFactory
 */
class ControllerAbstractFactory implements AbstractFactoryInterface
{
    /**
     * @var array
     */
    private $resourceMappings = [
        ApplicationController::class                    => 'resource-applications',
        CertificateProviderController::class            => 'resource-certificate-provider',
        CorrespondentController::class                  => 'resource-correspondent',
        DonorController::class                          => 'resource-donor',
        InstructionController::class                    => 'resource-instruction',
        LockController::class                           => 'resource-lock',
        NotifiedPeopleController::class                 => 'resource-notified-people',
        PaymentController::class                        => 'resource-payment',
        PdfController::class                            => 'resource-pdfs',
        PreferenceController::class                     => 'resource-preference',
        PrimaryAttorneyController::class                => 'resource-primary-attorneys',
        PrimaryAttorneyDecisionsController::class       => 'resource-primary-attorney-decisions',
        RepeatCaseNumberController::class               => 'resource-repeat-case-number',
        ReplacementAttorneyController::class            => 'resource-replacement-attorneys',
        ReplacementAttorneyDecisionsController::class   => 'resource-replacement-attorney-decisions',
        SeedController::class                           => 'resource-seed',
        TypeController::class                           => 'resource-type',
        UserController::class                           => 'resource-users',
        WhoAreYouController::class                      => 'resource-who-are-you',
        WhoIsRegisteringController::class               => 'resource-who-is-registering',
    ];

    /**
     * Can the factory create an instance for the service?
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        return (class_exists($requestedName) && is_subclass_of($requestedName, AbstractController::class));
    }

    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws Exception if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if (!$this->canCreate($container, $requestedName)) {
            throw new ServiceNotFoundException(sprintf(
                'Abstract factory %s can not create the requested service %s',
                get_class($this),
                $requestedName
            ));
        }

        //  Create the controller injecting the appropriate resource
        $resource = $container->get($this->resourceMappings[$requestedName]);

        $controller = new $requestedName($resource);

        // Ensure it's Dispatchable...
        if (($controller instanceof Dispatchable) === false) {
            throw new RuntimeException('Requested controller class is not Dispatchable');
        }

        return $controller;
    }
}