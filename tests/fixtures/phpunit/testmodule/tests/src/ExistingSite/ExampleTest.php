<?php

namespace Drupal\Tests\testmodule\ExistingSite;

use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\user\Entity\User;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * A model test case using traits from Drupal Test Traits.
 */
class ExampleTest extends ExistingSiteBase
{

    protected function setUp()
    {
        parent::setUp();

        // Cause tests to fail if an error is sent to Drupal logs.
        $this->failOnLoggedErrors();
    }

    /**
     * An example test method; note that Drupal API's and Mink are available.
     *
     * @throws \Drupal\Core\Entity\EntityStorageException
     * @throws \Drupal\Core\Entity\EntityMalformedException
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function testLlama()
    {
        // Create a "Llama" article. Will be automatically cleaned up at end of test.
        $node = $this->createNode([
            'title' => 'Llama',
            'type' => 'page',
            'uid' => 1,
        ]);
        $node->setPublished()->save();
        $this->assertEquals(1, $node->getOwnerId());

        // We can browse pages.
        $this->drupalGet($node->toUrl());
        $this->assertSession()->statusCodeEquals(200);
    }
}
