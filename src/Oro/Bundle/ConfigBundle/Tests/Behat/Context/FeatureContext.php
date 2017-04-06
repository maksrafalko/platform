<?php

namespace Oro\Bundle\ConfigBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\ActivityListBundle\Tests\Behat\Element\ActivityList;
use Oro\Bundle\CalendarBundle\Tests\Behat\Element\ColorsAwareInterface;
use Oro\Bundle\FormBundle\Tests\Behat\Element\AllowedColorsMapping;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\TableRow;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\FixtureLoaderAwareInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\FixtureLoaderDictionary;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class FeatureContext extends OroFeatureContext implements
    KernelAwareContext,
    FixtureLoaderAwareInterface,
    OroPageObjectAware
{
    use KernelDictionary, FixtureLoaderDictionary, PageObjectDictionary, AllowedColorsMapping;

    /**
     * Click link on sidebar in configuration menu
     *
     * Example: Given I click "Inventory" on configuration sidebar
     *
     * @When /^(?:|I )click "(?P<link>(?:[^"]|\\")*)" on configuration sidebar$/
     */
    public function clickLinkOnConfigurationSidebar($link)
    {
        $sidebarConfigMenu = $this->getPage()->find('css', 'div.system-configuration-container div.left-panel');
        $sidebarConfigMenu->clickLink($link);
    }

    /**
     * @var OroMainContext
     */
    private $oroMainContext;

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();
        $this->oroMainContext = $environment->getContext(OroMainContext::class);
    }

    /**
     * Asserts that grid has sticky (css:fixed) header
     *
     * @Then /^I see that grid has scrollable header$/
     */
    public function iSeeThatGridHasScrollableHeader()
    {
        self::assertTrue(
            $this->oroMainContext->isNodeVisible('.floatThead.floatThead-fixed'),
            'Grid header have no fixed classes'
        );
    }

    /**
     * Asserts that grid has no sticky header
     *
     * @Then /^I see that grid header is sticky$/
     */
    public function iSeeThatGridHeaderIsSticky()
    {
        self::assertFalse(
            $this->oroMainContext->isNodeVisible('.floatThead.floatThead-fixed'),
            'Grid header still have fixed classes'
        );
    }

    /**
     * Asserts that <Element> items are be sorted in provided order
     *
     * Example: Then ActivityList must be sorted ascending by updated date
     *
     * @Given /^(?P<name>(?:[\w\s]+)) must be sorted (?P<order>(?:ascending|descending)) by updated date$/
     */
    public function activityListMustBeSortedBy($name, $order)
    {
        /** @var ActivityList $list */
        $list = $this->elementFactory->createElement($name);

        $actual = [];
        foreach ($list->getItems() as $item) {
            if ($item instanceof TableRow) {
                $actual[] = $item->getCellValue('Updated At');
            } else {
                $actual[] = $item->getCreatedAtDate();
            }
        }

        $expected = $actual;
        $order = $order == 'ascending' ? SORT_ASC : SORT_DESC;

        array_multisort($expected, $order);

        self::assertEquals($expected, $actual, "Failed asserting that activity list sorted $order");
    }

    /**
     * Asserts records in activity list with provided table one by one
     * Example: Then I see following records in activity list with provided order:
     *             | Merry christmas |
     *             | Happy new year  |
     *             | Call with Jenny |
     *
     * @Then /^I see following records in activity list with provided order:$/
     */
    public function iSeeFollowingRecordsWithOrder(TableNode $table)
    {
        /** @var ActivityList $list */
        $list = $this->elementFactory->createElement('ActivityList');

        foreach ($list->getItems() as $key => $item) {
            // break cycle when all provided items checked
            if (count($table->getRows()) <= $key) {
                break;
            }
            self::assertEquals($table->getRow($key)[0], $item->getTitle());
        }
    }

    /**
     * Asserts visibility of provided sidebar
     *
     * Example: Then right sidebar is out of sight
     *          Then left sidebar is visible
     *
     * @Given /^(left|right) sidebar is (visible|out of sight)$/
     */
    public function leftSidebarIsVisible($position, $visibility)
    {
        $sidebarPanel = $this->getPage()->findVisible('css', "div[id^='sidebar-$position']");

        if ($visibility == 'visible') {
            self::assertNotNull($sidebarPanel, "Failed asserting that $position sidebar is visible");
        } else {
            self::assertNull($sidebarPanel, "Failed asserting that $position sidebar is invisible");
        }
    }

    /**
     * Asserts provided color array with available blocks on page, one by one element
     *
     * If provided color count less than existing on form - will be
     * checked first N elements begins from start
     *
     * Example:  Then I should see following available "Event Form" colors:
     *             | Apple green, Cornflower Blue, Mercury |
     *
     *           Then I should see following available "TaxonomyForm" colors:
     *             | Cornflower Blue, Mercury, Melrose, Mauve |
     *
     * @see AllowedColorsMapping for full list of available color names
     *
     * @Then /^I should see following available "(.+)" colors:$/
     */
    public function iShouldSeeFollowingAvailableEventColors($target, TableNode $table)
    {
        if (!$this->elementFactory->hasElement($target)) {
            throw new \InvalidArgumentException(sprintf('Could not find element with "%s" name', $target));
        }

        /** @var ColorsAwareInterface $form */
        $form = $this->elementFactory->createElement($target);
        $actual = $form->getAvailableColors();

        // parse provided color list to an array
        $expectedNames = $table->getRow(0);
        $expectedNames = explode(', ', reset($expectedNames));

        foreach ($expectedNames as $expectedName) {
            $currentActual = array_shift($actual);
            $currentExpected = $this->getHexByColorName($expectedName);
            self::assertEquals(
                $currentExpected,
                $currentActual,
                "Provided ($currentExpected) and found ($currentActual) on page colors are different"
            );
        }
    }
}
