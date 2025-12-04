<?php

declare(strict_types=1);

namespace HozoPHP\Tests;

use PHPUnit\Framework\TestCase;
use HozoPHP\OntologyManager;

class OntologyManagerTest extends TestCase
{
    private OntologyManager $ontology;
    private string $testOntologyPath;

    protected function setUp(): void
    {
        $this->testOntologyPath = dirname(__DIR__) . '/ontology/';
        $this->ontology = new OntologyManager($this->testOntologyPath, '20220916-sample.xml');
    }

    public function testCanInstantiate(): void
    {
        $this->assertInstanceOf(OntologyManager::class, $this->ontology);
    }

    public function testGetAllConceptsReturnsArray(): void
    {
        $concepts = $this->ontology->getAllConcepts();
        $this->assertIsArray($concepts);
        $this->assertNotEmpty($concepts);
    }

    public function testGetConceptInfoFromID(): void
    {
        // "Any" concept (root concept)
        $concept = $this->ontology->getConceptInfoFromID('1509010690552_n0');
        $this->assertIsArray($concept);
        $this->assertEquals('Any', $concept['label']);
    }

    public function testGetConceptInfoFromLabel(): void
    {
        $concept = $this->ontology->getConceptInfoFromLabel('Any');
        $this->assertIsArray($concept);
        $this->assertEquals('1509010690552_n0', $concept['id']);
    }

    public function testGetConceptInfoFromIDReturnsEmptyForNonExistent(): void
    {
        $concept = $this->ontology->getConceptInfoFromID('non_existent_id');
        $this->assertEmpty($concept);
    }

    public function testGetConceptInfoFromLabelReturnsEmptyForNonExistent(): void
    {
        $concept = $this->ontology->getConceptInfoFromLabel('存在しない概念');
        $this->assertEmpty($concept);
    }

    public function testGetChildrenConcepts(): void
    {
        // Get children of root concept "Any"
        $children = $this->ontology->getChildrenConcepts('1509010690552_n0');
        $this->assertIsArray($children);
    }

    public function testGetParentConcept(): void
    {
        // Get parent of "活動" concept
        $concept = $this->ontology->getConceptInfoFromLabel('活動');
        if (!empty($concept)) {
            $parent = $this->ontology->getParentConcept($concept['id']);
            $this->assertIsArray($parent);
        }
    }

    public function testGetAncestorConcepts(): void
    {
        // Get ancestors of "問い生成活動" concept
        $concept = $this->ontology->getConceptInfoFromLabel('問い生成活動');
        if (!empty($concept)) {
            $ancestors = $this->ontology->getAncestorConcepts($concept['id']);
            $this->assertIsArray($ancestors);
        }
    }

    public function testGetDescendantConcepts(): void
    {
        // Get descendants of "Any"
        $descendants = $this->ontology->getDescendantConcepts('1509010690552_n0');
        $this->assertIsArray($descendants);
    }

    public function testGetISARelationshipList(): void
    {
        $isaList = $this->ontology->getISARelationshipList();
        $this->assertIsArray($isaList);
    }

    public function testGetPartOfConceptInfo(): void
    {
        // "活動" has part-of concepts
        $concept = $this->ontology->getConceptInfoFromLabel('活動');
        if (!empty($concept)) {
            $parts = $this->ontology->getPartOfConceptInfo($concept['id']);
            $this->assertIsArray($parts);
        }
    }

    public function testGetAllInstance(): void
    {
        $instances = $this->ontology->getAllInstance();
        $this->assertIsArray($instances);
    }

    public function testTreatOntologyString(): void
    {
        $xmlContent = file_get_contents($this->testOntologyPath . '20220916-sample.xml');
        $newOntology = new OntologyManager($this->testOntologyPath);
        $newOntology->treatOntologyString($xmlContent);

        $concepts = $newOntology->getAllConcepts();
        $this->assertIsArray($concepts);
        $this->assertNotEmpty($concepts);
    }

    public function testGetOntologyFilePath(): void
    {
        $path = $this->ontology->getOntologyFilePath();
        $this->assertStringContainsString('20220916-sample.xml', $path);
    }

    public function testGetOntologyObject(): void
    {
        $object = $this->ontology->getOntologyObject();
        $this->assertIsArray($object);
    }

    public function testFlatten(): void
    {
        $nested = [[1, 2], [3, [4, 5]]];
        $flattened = $this->ontology->flatten($nested);
        $this->assertEquals([1, 2, 3, 4, 5], $flattened);
    }

    public function testGetAncestorSubConcepts(): void
    {
        // Test getting all sub-concepts including ancestors
        $concept = $this->ontology->getConceptInfoFromLabel('問い生成活動');
        if (!empty($concept)) {
            $subConcepts = $this->ontology->getAncestorSubConcepts($concept['id']);
            $this->assertIsArray($subConcepts);
        }
    }
}
