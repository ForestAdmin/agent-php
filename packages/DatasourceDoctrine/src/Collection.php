<?php

namespace ForestAdmin\AgentPHP\DatasourceDoctrine;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\Persistence\Mapping\MappingException;
use ForestAdmin\AgentPHP\Agent\Utils\QueryConverter;
use ForestAdmin\AgentPHP\BaseDatasource\BaseCollection;
use ForestAdmin\AgentPHP\BaseDatasource\Contracts\BaseDatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;
use Illuminate\Support\Arr;

class Collection extends BaseCollection
{
    protected string $className;

    /**
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function __construct(protected BaseDatasourceContract $datasource, public ClassMetadata $entityMetadata)
    {
        parent::__construct($datasource, $entityMetadata->reflClass->getShortName(), $entityMetadata->getTableName());
        $this->className = $entityMetadata->getName();
        $this->mapRelationshipsToFields();
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function mapRelationshipsToFields(): void
    {
        $relationships = $this->entityMetadata->associationMappings;
        foreach ($relationships as $key => $value) {
            $type = $this->getRelationType($this->entityMetadata->reflFields[$key]->getAttributes());
            if ($type) {
                match ($type) {
                    'ManyToMany' => $this->addManyToMany($key, $value['joinTable'], $value['targetEntity'], $value['mappedBy']),
                    'ManyToOne'  => $this->addManyToOne($key, $value['joinColumns'][0], $value['targetEntity']),
                    'OneToMany'  => $this->addOneToMany($key, $value['targetEntity'], $value['mappedBy']),
                    'OneToOne'   => $this->addOneToOne($key, $value['joinColumns'], $value['targetEntity'], $value['mappedBy']),
                    default      => null
                };
            }
        }
    }

    public function getIdentifier(): string
    {
        return $this->entityMetadata->getSingleIdReflectionProperty()->getName();
    }

    /**
     * @param Caller $caller
     * @param array  $data
     * @return array|void
     * @codeCoverageIgnore
     */
    public function create(Caller $caller, array $data)
    {
        $data[$this->getIdentifier()] = $this->entityMetadata->idGenerator->generateId($this->datasource->getEntityManager(), new $this->className());
        $query = QueryConverter::of($this, $caller->getTimezone())->getQuery();
        $id = $query->insertGetId($data);

        $filter = new Filter(
            conditionTree: new ConditionTreeLeaf($this->getIdentifier(), Operators::EQUAL, $id)
        );

        return Arr::dot(QueryConverter::of($this, $caller->getTimezone(), $filter)->getQuery()->first());
    }

    /**
     * @param array $reflectionAttributes
     * @return string|null
     */
    protected function getRelationType(array $reflectionAttributes): ?string
    {
        foreach ($reflectionAttributes as $attribute) {
            if (in_array($attribute->getName(), [ManyToMany::class, ManyToOne::class, OneToMany::class, OneToOne::class], true)) {
                return str_replace('Doctrine\ORM\Mapping\\', '', $attribute->getName());
            }
        }

        return null;
    }

    /**
     * @param string $name
     * @param array  $joinColumn
     * @param string $related
     * @return void
     * @throws MappingException
     * @throws \ReflectionException
     */
    protected function addManyToOne(string $name, array $joinColumn, string $related): void
    {
        $relatedMeta = $this->datasource->getEntityManager()->getMetadataFactory()->getMetadataFor($related);
        $relationField = new ManyToOneSchema(
            foreignKey: $joinColumn['name'],
            foreignKeyTarget: $relatedMeta->fieldNames[$joinColumn['referencedColumnName']],
            foreignCollection: (new \ReflectionClass($related))->getShortName(),
        );

        $this->addField($name, $relationField);
    }

    /**
     * @param string      $name
     * @param array       $joinColumn
     * @param string      $related
     * @param string|null $mappedField
     * @return void
     * @throws MappingException
     * @throws \ReflectionException
     */
    protected function addOneToOne(string $name, array $joinColumn, string $related, ?string $mappedField = null): void
    {
        $relatedMeta = $this->datasource->getEntityManager()->getMetadataFactory()->getMetadataFor($related);
        if ($mappedField && isset($relatedMeta->associationMappings[$mappedField])) {
            $joinColumn = $relatedMeta->associationMappings[$mappedField]['joinColumns'];
            $relationField = new OneToOneSchema(
                originKey: $this->entityMetadata->fieldNames[$joinColumn[0]['referencedColumnName']],
                originKeyTarget: $joinColumn[0]['name'],
                foreignCollection: (new \ReflectionClass($related))->getShortName(),
            );
        } else {
            $relationField = new OneToOneSchema(
                originKey: $joinColumn[0]['name'],
                originKeyTarget: $relatedMeta->fieldNames[$joinColumn[0]['referencedColumnName']],
                foreignCollection: (new \ReflectionClass($related))->getShortName(),
            );
        }

        $this->addField($name, $relationField);
    }

    /**
     * @param string      $name
     * @param array       $joinTable
     * @param string      $related
     * @param string|null $mappedField
     * @return void
     * @throws MappingException
     * @throws \ReflectionException
     */
    protected function addManyToMany(string $name, array $joinTable, string $related, ?string $mappedField = null): void
    {
        $relatedMeta = $this->datasource->getEntityManager()->getMetadataFactory()->getMetadataFor($related);
        if ($mappedField && isset($relatedMeta->associationMappings[$mappedField])) {
            $joinTable = $relatedMeta->associationMappings[$mappedField]['joinTable'];
            $schemaTable = $this->datasource
                ->getEntityManager()
                ->getConnection()
                ->createSchemaManager()
                ->introspectTable($relatedMeta->associationMappings[$mappedField]['joinTable']['name']);
            $customAttributes = [
                'foreignKey'          => $joinTable['joinColumns'][0]['name'],
                'foreignKeyTarget'    => $relatedMeta->fieldNames[$joinTable['joinColumns'][0]['referencedColumnName']],
                'originKey'           => $joinTable['inverseJoinColumns'][0]['name'],
                'originKeyTarget'     => $this->entityMetadata->fieldNames[$joinTable['inverseJoinColumns'][0]['referencedColumnName']],
            ];
        } else {
            $schemaTable = $this->datasource
                ->getEntityManager()
                ->getConnection()
                ->createSchemaManager()
                ->introspectTable($joinTable['name']);

            $customAttributes = [
                'foreignKey'          => $joinTable['inverseJoinColumns'][0]['name'],
                'foreignKeyTarget'    => $relatedMeta->fieldNames[$joinTable['inverseJoinColumns'][0]['referencedColumnName']],
                'originKey'           => $joinTable['joinColumns'][0]['name'],
                'originKeyTarget'     => $this->entityMetadata->fieldNames[$joinTable['joinColumns'][0]['referencedColumnName']],
            ];
        }

        if ($this->datasource->getCollections()->first(fn ($item) => $item->getName() === $schemaTable->getName()) === null) {
            // create throughCollection
            $this->datasource->addCollection(
                new ThroughCollection(
                    $this->datasource,
                    [
                        'name'               => $schemaTable->getName(),
                        'columns'            => $schemaTable->getColumns(),
                        'foreignKeys'        => $schemaTable->getForeignKeys(),
                        'primaryKey'         => $schemaTable->getPrimaryKey(),
                        'foreignCollections' => [
                            $this->tableName                        => $this->name,
                            $relatedMeta->getTableName()            => $relatedMeta->reflClass->getShortName(),
                        ],
                    ]
                )
            );
        }

        $defaultAttributes = [
            'throughCollection'   => $schemaTable->getName(),
            'foreignCollection'   => (new \ReflectionClass($related))->getShortName(),
        ];

        $relationField = new ManyToManySchema(...array_merge($defaultAttributes, $customAttributes));

        $this->addField($name, $relationField);
    }

    /**
     * @param string $name
     * @param string $related
     * @param string $mappedField
     * @return void
     * @throws MappingException
     * @throws \ReflectionException
     * @throws \Exception
     */
    protected function addOneToMany(string $name, string $related, string $mappedField): void
    {
        $relatedMeta = $this->datasource->getEntityManager()->getMetadataFactory()->getMetadataFor($related);
        if (isset($relatedMeta->associationMappings[$mappedField])) {
            $joinColumn = $relatedMeta->associationMappings[$mappedField]['joinColumns'][0];
            $relationField = new OneToManySchema(
                originKey: $joinColumn['name'],
                originKeyTarget: $this->entityMetadata->fieldNames[$joinColumn['referencedColumnName']],
                foreignCollection: (new \ReflectionClass($related))->getShortName(),
            );

            $this->addField($name, $relationField);
        }
    }
}
