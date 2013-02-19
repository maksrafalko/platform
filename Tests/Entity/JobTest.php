<?php
namespace Oro\Bundle\DataFlowBundle\Tests\Entity;

use Oro\Bundle\DataFlowBundle\Entity\Configuration;
use Oro\Bundle\DataFlowBundle\Entity\Job;
use Oro\Bundle\DataFlowBundle\Entity\Connector;

/**
 * Test related class
 *
 * @author    Nicolas Dupont <nicolas@akeneo.com>
 * @copyright 2012 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/MIT MIT
 *
 */
class JobTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Job
     */
    protected $job;

    /**
     * Setup
     */
    public function setup()
    {
        $this->job = new Job();
    }

    /**
     * Test related methods
     */
    public function testGettersSetters()
    {
        $this->assertNull($this->job->getId());
        $this->assertNull($this->job->getServiceId());
        $this->assertNull($this->job->getConfiguration());
        $this->assertNull($this->job->getConnector());
        $this->assertNull($this->job->getDescription());

        $configuration = new Configuration();
        $connector = new Connector();
        $this->job->setId(1);
        $this->job->setServiceId('my.job.id');
        $this->job->setConfiguration($configuration);
        $this->job->setConnector($connector);
        $this->job->setDescription('my job description');

        $this->assertEquals($this->job->getServiceId(), 'my.job.id');
        $this->assertEquals($this->job->getConfiguration(), $configuration);
        $this->assertEquals($this->job->getConnector(), $connector);
        $this->assertEquals($this->job->getDescription(), 'my job description');
    }
}
