<?php

namespace Phalcon\Test\Unit\Events;

use ComponentX;
use ComponentY;
use Phalcon\Events\Event;
use Phalcon\Events\Manager;
use Phalcon\Test\Module\UnitTest;
use Phalcon\Test\Listener\FirstListener;
use Phalcon\Test\Listener\ThirdListener;
use Phalcon\Test\Listener\SecondListener;

/**
 * \Phalcon\Test\Unit\Events\ManagerTest
 * Tests the \Phalcon\Events\Manager component
 *
 * @copyright (c) 2011-2017 Phalcon Team
 * @link      https://www.phalconphp.com
 * @author    Andres Gutierrez <andres@phalconphp.com>
 * @author    Serghei Iakovlev <serghei@phalconphp.com>
 * @package   Phalcon\Test\Unit\Events
 *
 * The contents of this file are subject to the New BSD License that is
 * bundled with this package in the file docs/LICENSE.txt
 *
 * If you did not receive a copy of the license and are unable to obtain it
 * through the world-wide-web, please send an email to license@phalconphp.com
 * so that we can send you a copy immediately.
 */
class ManagerTest extends UnitTest
{
    protected $listener;

    /**
     * executed before each test
     */
    public function _before()
    {
        parent::_before();

        include_once PATH_DATA . 'events/ComponentX.php';
        include_once PATH_DATA . 'events/ComponentY.php';
    }

    /**
     * Tests attaching event listeners by event name after detaching all
     *
     * @test
     * @issue  1331
     * @author Kamil Skowron <kamil@hedonsoftware.com>
     * @since  2014-05-28
     */
    public function attachingListenersByEventNameAfterDetachingAll()
    {
        $this->specify(
            'Attaching event listeners by event name fails if preceded by detachment of all listeners for that type',
            function () {
                $first  = new FirstListener();
                $second = new SecondListener();

                $component = new ComponentX();

                $eventsManager = new Manager();
                $eventsManager->attach('log', $first);

                $component->setEventsManager($eventsManager);

                $logListeners = $component->getEventsManager()->getListeners('log');

                expect($logListeners)->count(1);
                expect($logListeners[0])->isInstanceOf(FirstListener::class);

                $component->getEventsManager()->attach('log', $second);
                $logListeners = $component->getEventsManager()->getListeners('log');

                expect($logListeners)->count(2);
                expect($logListeners[0])->isInstanceOf(FirstListener::class);
                expect($logListeners[1])->isInstanceOf(SecondListener::class);

                $component->getEventsManager()->detachAll('log');
                $logListeners = $component->getEventsManager()->getListeners('log');

                expect($logListeners)->isEmpty();

                $component->getEventsManager()->attach('log', $second);
                $logListeners = $component->getEventsManager()->getListeners('log');

                expect($logListeners)->count(1);
                expect($logListeners[0])->isInstanceOf(SecondListener::class);
            }
        );
    }

    /**
     * Tests using event listeners
     *
     * @test
     * @author Andres Gutierrez <andres@phalconphp.com>
     * @since  2012-08-14
     */
    public function usingEvents()
    {
        $this->specify(
            'Using event listeners does not work as expected',
            function () {
                $listener1 = new ThirdListener();
                $listener1->setTestCase($this);

                $listener2 = new ThirdListener();
                $listener2->setTestCase($this);

                $eventsManager = new Manager();
                $eventsManager->attach('dummy', $listener1);

                $componentX = new ComponentX();
                $componentX->setEventsManager($eventsManager);

                $componentY = new ComponentY();
                $componentY->setEventsManager($eventsManager);

                $componentX->leAction();
                $componentX->leAction();

                $componentY->leAction();
                $componentY->leAction();
                $componentY->leAction();

                expect($listener1->getBeforeCount())->equals(2);
                expect($listener1->getAfterCount())->equals(2);

                $eventsManager->attach('dummy', $listener2);

                $componentX->leAction();
                $componentX->leAction();

                expect($listener1->getBeforeCount())->equals(4);
                expect($listener1->getAfterCount())->equals(4);

                expect($listener2->getBeforeCount())->equals(2);
                expect($listener2->getAfterCount())->equals(2);

                expect($this->listener)->same($listener2);

                $eventsManager->detach('dummy', $listener1);

                $componentX->leAction();
                $componentX->leAction();

                expect($listener1->getBeforeCount())->equals(4);
                expect($listener1->getAfterCount())->equals(4);

                expect($listener2->getBeforeCount())->equals(4);
                expect($listener2->getAfterCount())->equals(4);
            }
        );
    }

    /**
     * Tests using events with priority
     *
     * @test
     * @author Vladimir Khramov <quant13@gmail.com>
     * @since  2014-12-09
     */
    public function usingEventsWithPriority()
    {
        $this->specify(
            'Using event listeners with priority does not work as expected',
            function () {
                $listener1 = new ThirdListener();
                $listener1->setTestCase($this);

                $listener2 = new ThirdListener();
                $listener2->setTestCase($this);

                $eventsManager = new Manager();
                $eventsManager->enablePriorities(true);

                $eventsManager->attach('dummy', $listener1, 100);

                $componentX = new ComponentX();
                $componentX->setEventsManager($eventsManager);

                $componentY = new ComponentY();
                $componentY->setEventsManager($eventsManager);

                $componentX->leAction();
                $componentX->leAction();

                $componentY->leAction();
                $componentY->leAction();
                $componentY->leAction();

                expect($listener1->getBeforeCount())->equals(2);
                expect($listener1->getAfterCount())->equals(2);

                $eventsManager->attach('dummy', $listener2, 150);

                $componentX->leAction();
                $componentX->leAction();

                expect($listener1->getBeforeCount())->equals(4);
                expect($listener1->getAfterCount())->equals(4);

                expect($listener2->getBeforeCount())->equals(2);
                expect($listener2->getAfterCount())->equals(2);

                expect($this->listener)->same($listener1);

                $eventsManager->detach('dummy', $listener1);

                $componentX->leAction();
                $componentX->leAction();

                expect($listener1->getBeforeCount())->equals(4);
                expect($listener1->getAfterCount())->equals(4);

                expect($listener2->getBeforeCount())->equals(4);
                expect($listener2->getAfterCount())->equals(4);
            }
        );
    }

    /**
     * Tests using events propagation
     *
     * @test
     * @author Andres Gutierrez <andres@phalconphp.com>
     * @since  2012-11-11
     */
    public function stopEventsInEventsManager()
    {
        $this->specify(
            'The ability to stop events in EventsManager does not work as expected',
            function () {
                $number        = 0;
                $eventsManager = new Manager();

                $propagationListener = function (Event $event, $component, $data) use (&$number) {
                    $number++;
                    $event->stop();
                };

                $eventsManager->attach('some-type', $propagationListener);
                $eventsManager->attach('some-type', $propagationListener);

                $eventsManager->fire('some-type:beforeSome', $this);

                expect($number)->equals(1);
            }
        );
    }

    public function setLastListener($listener)
    {
        $this->listener = $listener;
    }
}
