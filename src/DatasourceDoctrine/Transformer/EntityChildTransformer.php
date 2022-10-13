<?php

namespace ForestAdmin\AgentPHP\DatasourceDoctrine\Transformer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use ForestAdmin\AgentPHP\DatasourceDoctrine\Utils\DataTypes;
use League\Fractal\TransformerAbstract;
use Symfony\Component\PropertyAccess\PropertyAccess;

class EntityChildTransformer extends TransformerAbstract
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

        foreach ($entityMetadata->getFieldNames() as $fieldName) {
            $data[$fieldName] = DataTypes::renderValue($entityMetadata->getFieldMapping($fieldName)['type'], $propertyAccessor->getValue($entity, $fieldName));
        }


        return $data;
    }
}
