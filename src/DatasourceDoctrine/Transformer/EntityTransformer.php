<?php

namespace ForestAdmin\AgentPHP\DatasourceDoctrine\Transformer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;
use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Serializer\Transformers\BaseTransformer;
use Illuminate\Support\Str;
use Nicolas\SymfonyForestAdmin\Datasource\Collection;
use Nicolas\SymfonyForestAdmin\Utils\DataTypes;
use Symfony\Component\PropertyAccess\PropertyAccess;

class EntityTransformer extends BaseTransformer
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * @param $entity
     * @return array
     * @throws \Doctrine\ORM\Mapping\MappingException
     * @throws \Doctrine\Persistence\Mapping\MappingException
     * @throws \ReflectionException
     */
    public function transform($entity)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $data = [];

        /** @var ClassMetadata $entityMetadata */
        $entityMetadata = $this->entityManager->getMetadataFactory()->getMetadataFor(get_class($entity));

        /** @var Collection $forestCollection */
        $forestCollection = AgentFactory::get('datasource')->getCollection($entityMetadata->reflClass->getShortName());

        $relations = array_keys($entityMetadata->getAssociationMappings());

        foreach ($relations as $relation) {
            if ($this->isSimpleRelation($entityMetadata->reflFields[$relation]->getAttributes())) {
                $value = $propertyAccessor->getValue($entity, $relation);
                $forestCollectionRelation = $forestCollection->getFields()[$relation];
                if ($forestCollectionRelation->getType() === 'ManyToOne') {
                    $data[$relation] = $value ? $propertyAccessor->getValue($value, $forestCollectionRelation->getForeignKeyTarget()) : null;
                } else {
                    $data[$relation] = $value ? $propertyAccessor->getValue($value, $forestCollectionRelation->getOriginKeyTarget()) : null;
                }

                $this->defaultIncludes[] = $relation;
                if ($value) {
                    $type = class_basename($entityMetadata->getAssociationMapping($relation)['targetEntity']);
                    $this->addMethod('include' . Str::ucfirst($relation), fn () => $this->item($value, new EntityChildTransformer($this->entityManager), $type));
                } else {
                    $this->addMethod('include' . Str::ucfirst($relation), fn () => $this->null());
                }
            }
        }

        foreach ($entityMetadata->getFieldNames() as $fieldName) {
            $data[$fieldName] = DataTypes::renderValue($entityMetadata->getFieldMapping($fieldName)['type'], $propertyAccessor->getValue($entity, $fieldName));
        }

        return $data;
    }

    private function isSimpleRelation(array $reflectionAttributes): bool
    {
        foreach ($reflectionAttributes as $attribute) {
            if (in_array($attribute->getName(), [ManyToOne::class, OneToOne::class], true)) {
                return true;
            }
        }

        return false;
    }
}
