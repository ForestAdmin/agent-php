<?php

namespace ForestAdmin\AgentPHP\DatasourceDoctrine;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\Mapping\MappingException;
use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\FrontendFilterable;
use ForestAdmin\AgentPHP\Agent\Utils\QueryCharts;
use ForestAdmin\AgentPHP\Agent\Utils\QueryConverter;
use ForestAdmin\AgentPHP\DatasourceDoctrine\Utils\DataTypes;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection as ForestCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\PropertyAccess\PropertyAccess;

class Collection extends ForestCollection
{
    protected string $tableName;

    /**
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function __construct(protected DoctrineDatasource $datasource, protected ClassMetadata $entityMetadata)
    {
        parent::__construct($datasource, $entityMetadata->reflClass->getShortName());

        $this->className = $entityMetadata->getName();
        $this->tableName = $this->entityMetadata->getTableName();
        $this->addFields($this->entityMetadata->fieldMappings);
        $this->mapRelationshipsToFields();
        $this->searchable = true;
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
                    'ManyToMany' => $this->addManyToMany($key, $value['joinTable'], $value['targetEntity'], $value['mappedBy'] ?? $value['inversedBy'], $value['mappedBy']),
                    'ManyToOne'  => $this->addManyToOne($key, $value['joinColumns'][0], $value['targetEntity'], $value['inversedBy']),
                    'OneToMany'  => $this->addOneToMany($key, $value['targetEntity'], $value['mappedBy']),
                    'OneToOne'   => $this->addOneToOne($key, $value['joinColumns'], $value['targetEntity'], $value['mappedBy'] ?? $value['inversedBy'], $value['mappedBy']),
                    default      => null
                };
            }
        }
    }

    /**
     * @throws \Exception
     */
    public function addFields(array $fields): void
    {
        foreach ($fields as $value) {
            $field = new ColumnSchema(
                columnType: DataTypes::getType($value['type']),
                filterOperators: FrontendFilterable::getRequiredOperators(DataTypes::getType($value['type'])),
                isPrimaryKey: in_array($value['fieldName'], $this->entityMetadata->getIdentifierFieldNames(), true),
                isReadOnly: false,
                isSortable: true,
                type: 'Column',
                defaultValue: array_key_exists('options', $value) && array_key_exists('default', $value['options']) ? $value['options']['default'] : null,
                enumValues: [], // todo
                validation: [], // todo
            );
            $this->addField($value['columnName'], $field);
        }
    }

    public function getIdentifier(): string
    {
        return $this->entityMetadata->getSingleIdReflectionProperty()->getName();
    }

    public function show(Caller $caller, Filter $filter, $id, Projection $projection)
    {
        return Arr::undot(QueryConverter::of($this, $caller->getTimezone(), $filter, $projection)->first());
    }

    public function list(Caller $caller, Filter $filter, Projection $projection): array
    {
        return QueryConverter::of($this, $caller->getTimezone(), $filter, $projection)
            ->get()
            ->map(fn ($record) => Arr::undot($record))
            ->toArray();
    }

    public function export(Caller $caller, Filter $filter, Projection $projection): array
    {
        $results = QueryConverter::of($this, $caller->getTimezone(), $filter, $projection)
            ->get()
            ->map(fn ($record) => Arr::undot($record))
            ->toArray();

        $relations = $projection->relations()->keys()->toArray();
        foreach ($results as &$result) {
            foreach ($result as $field => $value) {
                if (is_array($value) && in_array($field, $relations, true)) {
                    $result[$field] = array_shift($value);
                }
            }
        }

        return $results;
    }

    public function create(Caller $caller, array $data)
    {
        $entity = $this->setAttributesAndPersist(new $this->className(), $data);
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        return $this->datasource->getEntityManager()->getRepository($this->className)->find($propertyAccessor->getValue($entity, $this->getIdentifier()));
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function update(Caller $caller, Filter $filter, $id, array $patch)
    {
        $record = QueryConverter::of($filter, $this->datasource->getEntityManager(), $this->entityMetadata, $caller->getTimezone())
            ->getQuery()
            ->getSingleResult();

        return $this->setAttributesAndPersist($record, $patch);
    }

    public function delete(Caller $caller, Filter $filter, $id): void
    {
        $records = QueryConverter::of($filter, $this->datasource->getEntityManager(), $this->entityMetadata, $caller->getTimezone())
            ->getQuery()
            ->getResult();

        foreach ($records as $record) {
            $this->datasource->getEntityManager()->remove($record);
        }

        $this->datasource->getEntityManager()->flush();
    }

    /**
     * @throws \Exception
     */
    public function aggregate(Caller $caller, Filter $filter, Aggregation $aggregation, ?int $limit = null, ?string $chartType = null)
    {
        $query = QueryConverter::of($this, $caller->getTimezone(), $filter, new Projection());

        if ($chartType) {
            return QueryCharts::of($this, $query, $aggregation, $limit)
                ->{'query' . $chartType}()
                ->map(fn ($result) => is_array($result) || is_object($result) ? Arr::undot($result) : $result)
                ->toArray();
        } else {
            return $query->count();
        }
    }

    public function associate(Caller $caller, Filter $parentFilter, Filter $childFilter, OneToManySchema|ManyToManySchema $relation): void
    {
        $entity = QueryConverter::of($parentFilter, $this->datasource->getEntityManager(), $this->entityMetadata, $caller->getTimezone())
            ->getQuery()
            ->getSingleResult();

        if ($relation instanceof ManyToManySchema) {
            $target = $this->entityMetadata->getAssociationMappings()[$relation->getForeignKey()]['targetEntity'];
        } else {
            $target = $this->entityMetadata->getAssociationMappings()[$relation->getOriginKey()]['targetEntity'];
        }
        $targetEntityMetaData = $this->datasource->getEntityManager()->getMetadataFactory()->getMetadataFor($target);
        $targetEntity = QueryConverter::of($childFilter, $this->datasource->getEntityManager(), $targetEntityMetaData, $caller->getTimezone())
            ->getQuery()
            ->getSingleResult();

        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        if ($relation instanceof ManyToManySchema) {
            $values = $propertyAccessor->getValue($entity, $relation->getForeignKey())->toArray();
            $values[] = $targetEntity;
            $propertyAccessor->setValue($entity, $relation->getForeignKey(), $values);
        } else {
            $propertyAccessor->setValue($entity, $relation->getOriginKey(), [$targetEntity]);
        }

        $this->datasource->getEntityManager()->persist($entity);
        $this->datasource->getEntityManager()->flush();
    }

    public function dissociate(Caller $caller, Filter $parentFilter, Filter $childFilter, OneToManySchema|ManyToManySchema $relation): void
    {
        $entity = QueryConverter::of($parentFilter, $this->datasource->getEntityManager(), $this->entityMetadata, $caller->getTimezone())
            ->getQuery()
            ->getSingleResult();

        if ($relation instanceof ManyToManySchema) {
            $target = $this->entityMetadata->getAssociationMappings()[$relation->getForeignKey()]['targetEntity'];
        } else {
            $target = $this->entityMetadata->getAssociationMappings()[$relation->getOriginKey()]['targetEntity'];
        }
        $targetEntityMetaData = $this->datasource->getEntityManager()->getMetadataFactory()->getMetadataFor($target);
        $targetEntity = QueryConverter::of($childFilter, $this->datasource->getEntityManager(), $targetEntityMetaData, $caller->getTimezone())
            ->getQuery()
            ->getResult();

        if (! empty($targetEntity)) {
            $propertyAccessor = PropertyAccess::createPropertyAccessor();
            if ($relation instanceof ManyToManySchema) {
                $currentValues = $propertyAccessor->getValue($entity, $relation->getForeignKey())->toArray();
                $targetEntityIds = collect($targetEntity)->map(fn ($item) => $propertyAccessor->getValue($item, $relation->getForeignKeyTarget()));
                $values = collect($currentValues)->filter(
                    function ($item) use ($propertyAccessor, $relation, $targetEntityIds) {
                        if (! in_array($propertyAccessor->getValue($item, $relation->getForeignKeyTarget()), $targetEntityIds->all(), true)) {
                            return $propertyAccessor->getValue($item, $relation->getForeignKeyTarget());
                        }
                    }
                );
                $propertyAccessor->setValue($entity, $relation->getForeignKey(), $values);
            } else {
                $propertyAccessor->setValue($targetEntity, $relation->getInverseRelationName(), null);
            }

            $this->datasource->getEntityManager()->persist($entity);
            $this->datasource->getEntityManager()->flush();
        }
    }

    protected function setAttributesAndPersist($entity, array $data)
    {
        $attributes = $data['attributes'];
        $relationships = $data['relationships'] ?? [];
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        foreach ($attributes as $key => $value) {
            $propertyAccessor->setValue($entity, $key, $value);
        }

        foreach ($relationships as $key => $value) {
            $type = $this->getRelationType($this->entityMetadata->reflFields[$key]->getAttributes());
            $attributes = $value['data'];
            if ($type === 'ManyToOne') {
                $targetEntity = $this->entityMetadata->getAssociationMapping($key)['targetEntity'];
                $related = $this->datasource->getEntityManager()->getRepository($targetEntity)->find($attributes['id']);
                $propertyAccessor->setValue($entity, $key, $related);
            }
        }

        $this->datasource->getEntityManager()->persist($entity);
        $this->datasource->getEntityManager()->flush();

        return $entity;
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
     * @param string $inverseName
     * @return void
     * @throws MappingException
     * @throws \ReflectionException
     */
    protected function addManyToOne(string $name, array $joinColumn, string $related, string $inverseName): void
    {
        $relatedMeta = $this->datasource->getEntityManager()->getMetadataFactory()->getMetadataFor($related);
        $relationField = new ManyToOneSchema(
            foreignKey: $joinColumn['name'],
            foreignKeyTarget: $relatedMeta->fieldNames[$joinColumn['referencedColumnName']],
            foreignCollection: (new \ReflectionClass($related))->getShortName(),
            inverseRelationName: $inverseName,
        );

        $this->addField($name, $relationField);
    }

    /**
     * @param string      $name
     * @param array       $joinColumn
     * @param string      $related
     * @param string      $inverseName
     * @param string|null $mappedField
     * @return void
     * @throws MappingException
     * @throws \ReflectionException
     */
    protected function addOneToOne(string $name, array $joinColumn, string $related, string $inverseName, ?string $mappedField = null): void
    {
        $relatedMeta = $this->datasource->getEntityManager()->getMetadataFactory()->getMetadataFor($related);
        if ($mappedField) {
            // hasOne
            if (! array_key_exists($mappedField, $relatedMeta->associationMappings)) {
                throw new \Exception("The relation field `$mappedField` does not exist in the entity `$related`.");
            }

            $joinColumn = $relatedMeta->associationMappings[$mappedField]['joinColumns'];
            $relationField = new OneToOneSchema(
                originKey: $joinColumn[0]['name'],
                originKeyTarget: $this->entityMetadata->fieldNames[$joinColumn[0]['referencedColumnName']],
                foreignCollection: (new \ReflectionClass($related))->getShortName(),
                inverseRelationName: $inverseName
            );
        } else {
            $relationField = new OneToOneSchema(
                originKey: $joinColumn[0]['name'],
                originKeyTarget: $relatedMeta->fieldNames[$joinColumn[0]['referencedColumnName']],
                foreignCollection: (new \ReflectionClass($related))->getShortName(),
                inverseRelationName: $inverseName
            );
        }

        $this->addField($name, $relationField);
    }

    /**
     * @param string      $name
     * @param array       $joinTable
     * @param string      $related
     * @param string      $inverseName
     * @param string|null $mappedField
     * @return void
     * @throws MappingException
     * @throws \ReflectionException
     */
    protected function addManyToMany(string $name, array $joinTable, string $related, string $inverseName, ?string $mappedField = null): void
    {
        $relatedMeta = $this->datasource->getEntityManager()->getMetadataFactory()->getMetadataFor($related);

        if ($mappedField) {
            // manyToMany inversed
            if (! array_key_exists($mappedField, $relatedMeta->associationMappings)) {
                throw new \Exception("The relation field `$mappedField` does not exist in the entity `$related`.");
            }
            $joinTable = $relatedMeta->associationMappings[$mappedField]['joinTable'];
            $customAttributes = [
                'foreignKeyTarget' => $relatedMeta->fieldNames[$joinTable['joinColumns'][0]['referencedColumnName']],
                'originKeyTarget'  => $this->entityMetadata->fieldNames[$joinTable['inverseJoinColumns'][0]['referencedColumnName']],
            ];
        } else {
            $customAttributes = [
                'foreignKeyTarget' => $relatedMeta->fieldNames[$joinTable['inverseJoinColumns'][0]['referencedColumnName']],
                'originKeyTarget'  => $this->entityMetadata->fieldNames[$joinTable['joinColumns'][0]['referencedColumnName']],
            ];
        }

        $defaultAttributes = [
            'foreignKey'          => $name,
            'throughTable'        => $joinTable['name'],
            'originKey'           => $inverseName,
            'inverseRelationName' => $inverseName,
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
        if (! array_key_exists($mappedField, $relatedMeta->associationMappings)) {
            throw new \Exception("The relation field `$mappedField` does not exist in the entity `$related`.");
        }

        $joinColumn = $relatedMeta->associationMappings[$mappedField]['joinColumns'][0];
        $relationField = new OneToManySchema(
            originKey: $joinColumn['name'],
            originKeyTarget: $this->entityMetadata->fieldNames[$joinColumn['referencedColumnName']],
            foreignCollection: (new \ReflectionClass($related))->getShortName(),
            inverseRelationName: $mappedField,
        );

        $this->addField($name, $relationField);
    }

    public function toArray($record, ?Projection $projection = null): array
    {
        $entityMetadata = $this->datasource->getEntityManager()->getMetadataFactory()->getMetadataFor(get_class($record));
        $fields = $projection ?? $entityMetadata->getFieldNames();
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $serialized = [];

        foreach ($fields as $field) {
            if (Str::contains($field, ':')) {
                $fieldName = Str::before($field, ':');
                $value = $propertyAccessor->getValue($record, $fieldName);
                $serialized[$fieldName] = $value ? $this->toArray($value, new Projection(Str::after($field, ':'))) : null;
            } else {
                $value = $propertyAccessor->getValue($record, $field);
                $serialized[$field] = $value;
            }
        }

        return $serialized;
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }
}
