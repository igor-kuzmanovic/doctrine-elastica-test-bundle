<?php

declare(strict_types=1);

namespace DoctrineElasticaTestBundle\PHPUnit;

use PHPUnit\Event\Test\PreparationStarted as TestPreparationStartedEvent;
use PHPUnit\Event\Test\PreparationStartedSubscriber as TestPreparationStartedSubscriber;
use PHPUnit\Event\TestRunner\Finished as TestRunnerFinishedEvent;
use PHPUnit\Event\TestRunner\FinishedSubscriber as TestRunnerFinishedSubscriber;
use PHPUnit\Event\TestRunner\Started as TestRunnerStartedEvent;
use PHPUnit\Event\TestRunner\StartedSubscriber as TestRunnerStartedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

final class PHPUnitExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscriber(new class () implements TestRunnerStartedSubscriber {
            public function notify(TestRunnerStartedEvent $event): void
            {
                RuntimeState::reset();
            }
        });

        $facade->registerSubscriber(new class () implements TestPreparationStartedSubscriber {
            public function notify(TestPreparationStartedEvent $event): void
            {
                RuntimeState::rollbackPendingMutations();
                RuntimeState::beginTest();
            }
        });

        $facade->registerSubscriber(new class () implements TestRunnerFinishedSubscriber {
            public function notify(TestRunnerFinishedEvent $event): void
            {
                RuntimeState::finishTestRun();
            }
        });
    }
}
