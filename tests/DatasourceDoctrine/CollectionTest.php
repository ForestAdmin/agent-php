<?php

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Id\SequenceGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use ForestAdmin\AgentPHP\DatasourceDoctrine\Collection;
use ForestAdmin\AgentPHP\DatasourceDoctrine\DoctrineDatasource;
use Illuminate\Database\Capsule\Manager;
use Prophecy\Argument;
use Prophecy\Prophet;

beforeEach(closure: function () {
    global $classMetadata, $doctrineDatasource, $fakeClass, $entityUser, $entityDriverLicence, $entityBook;

    $entityUser = new class () {
        public function getShortName()
        {
            return 'user';
        }

        public function getAttributes()
        {
            return [
                new class () {
                    public function getName()
                    {
                        return 'Doctrine\ORM\Mapping\ManyToMany';
                    }
                },
            ];
        }
    };

    $entityCustomer = new class () {
        public function getShortName()
        {
            return 'customer';
        }

        public function getAttributes()
        {
            return [
                new class () {
                    public function getName()
                    {
                        return 'Doctrine\ORM\Mapping\ManyToOne';
                    }
                },
            ];
        }
    };

    $entityBook = new class () {
        public function getShortName()
        {
            return 'book';
        }

        public function getAttributes()
        {
            return [
                new class () {
                    public function getName()
                    {
                        return 'Doctrine\ORM\Mapping\OneToMany';
                    }
                },
            ];
        }
    };

    $entityOwner = new class () {
        public function getShortName()
        {
            return 'owner';
        }

        public function getAttributes()
        {
            return [
                new class () {
                    public function getName()
                    {
                        return 'Doctrine\ORM\Mapping\OneToOne';
                    }
                },
            ];
        }
    };

    $entityDriverLicence = new class () {
        public function getShortName()
        {
            return 'driverLicence';
        }

        public function getAttributes()
        {
            return [
                new class () {
                    public function getName()
                    {
                        return 'Doctrine\ORM\Mapping\OneToOne';
                    }
                },
            ];
        }
    };

    $prophet = new Prophet();
    $subClassMetadata = $prophet->prophesize(ClassMetadata::class);
    $subClassMetadata->name = 'App\Entity\User';
    $subClassMetadata->reflClass = new \ReflectionClass($entityUser);
    $subClassMetadata->fieldNames = [
        'id' => 'id',
    ];
    $subClassMetadata->associationMappings = [
        'categories' => [
            'fieldName'    => 'categories',
            'joinTable'    => [
                'name'               => 'categories_users',
                'joinColumns'        => [
                    [
                        'name'                 => 'category_id',
                        'referencedColumnName' => 'id',
                        'onDelete'             => 'CASCADE',
                    ],
                ],
                'inverseJoinColumns' => [
                    [
                        'name'                 => 'user_id',
                        'referencedColumnName' => 'id',
                        'onDelete'             => 'CASCADE',
                    ],
                ],
            ],
            'targetEntity' => 'App\Entity\Category',
            'mappedBy'     => 'users',
            'inversedBy'   => null,
        ],
        'category'   => [
            'fieldName'    => 'category',
            'joinColumns'  => [
                [
                    'name'                 => 'category_id',
                    'unique'               => false,
                    'nullable'             => false,
                    'onDelete'             => null,
                    'columnDefinition'     => null,
                    'referencedColumnName' => 'id',
                ],
            ],
            'cascade'      => [],
            'inversedBy'   => 'books',
            'targetEntity' => 'App\Entity\Category',
        ],
        'owner'      => [
            'fieldName'    => 'owner',
            'targetEntity' => 'App\Entity\User',
            'joinColumns'  => [
                [
                    'name'                 => 'owner_id',
                    'unique'               => true,
                    'nullable'             => false,
                    'onDelete'             => null,
                    'columnDefinition'     => null,
                    'referencedColumnName' => 'id',
                ],
            ],
        ],
    ];

    $metadataFactory = $prophet->prophesize(ClassMetadataFactory::class);
    $metadataFactory->getMetadataFor(Argument::type('string'))->willReturn($subClassMetadata->reveal());

    $schemaTable = $prophet->prophesize(Table::class);
    $schemaTable->getName()->willReturn('categories_users');
    $schemaTable->getColumns()->willReturn([]);
    $schemaTable->getForeignKeys()->willReturn([
        'fk_123' => new ForeignKeyConstraint(['user_id'], 'users', ['id']),
    ]);
    $schemaTable->getPrimaryKey()->willReturn(new Index('id', []));

    $abstractSchemaManager = $prophet->prophesize(AbstractSchemaManager::class);
    $abstractSchemaManager->introspectTable(Argument::type('string'))->willReturn($schemaTable->reveal());

    $connexion = $prophet->prophesize(Connection::class);
    $connexion->createSchemaManager()->willReturn($abstractSchemaManager->reveal());

    $capsuleManager = $prophet->prophesize(Manager::class);
    $capsuleManager->table(Argument::any(), Argument::any(), Argument::any())->willReturn('table-name');

    $entityManager = $prophet->prophesize(EntityManager::class);
    $entityManager->getMetadataFactory()->willReturn($metadataFactory->reveal());
    $entityManager->getConnection()->willReturn($connexion->reveal());

    $doctrineDatasource = $prophet->prophesize(DoctrineDatasource::class);
    $doctrineDatasource->getEntityManager()->willReturn($entityManager->reveal());
    $doctrineDatasource->getOrm()->willReturn($capsuleManager->reveal());

    $doctrineDatasource->getCollections()->willReturn(collect());
    $doctrineDatasource = $doctrineDatasource->reveal();

    $classMetadata = $prophet->prophesize(ClassMetadata::class);
    $classMetadata->fieldNames = [
        'id' => 'id',
    ];
    $classMetadata->reflClass = new \ReflectionClass($entityUser);
    $classMetadata->getName()->willReturn((new \ReflectionClass($entityUser))->getName());
    $classMetadata->getTableName()->willReturn('users');
    $classMetadata->getIdentifierFieldNames()->willReturn(['id']);
    $fakeClass = new class () {
        public function getName()
        {
            return 'id';
        }
    };
    $classMetadata->getSingleIdReflectionProperty()->willReturn($fakeClass);
    $classMetadata->reflFields = [
        'users'         => $entityUser,
        'comments'      => $entityUser,
        'customer'      => $entityCustomer,
        'books'         => $entityBook,
        'owner'         => $entityOwner,
        'driverLicence' => $entityDriverLicence,
    ];

    $classMetadata->associationMappings = [
        //many-to-many
        'users'         => [
            'fieldName'    => 'users',
            'joinTable'    => [],
            'targetEntity' => (new \ReflectionClass($entityUser))->getName(),
            'mappedBy'     => 'categories',
            'inversedBy'   => null,
        ],
        //many-to-many inverse
        'comments'      => [
            'fieldName'    => 'comments',
            'joinTable'    => [
                'name'               => 'comments_users',
                'joinColumns'        => [
                    [
                        'name'                 => 'comment_id',
                        'referencedColumnName' => 'id',
                        'onDelete'             => 'CASCADE',
                    ],
                ],
                'inverseJoinColumns' => [
                    [
                        'name'                 => 'user_id',
                        'referencedColumnName' => 'id',
                        'onDelete'             => 'CASCADE',
                    ],
                ],
            ],
            'targetEntity' => (new \ReflectionClass($entityUser))->getName(),
            'mappedBy'     => null,
            'inversedBy'   => 'comments',
        ],
        //many-to-one
        'customer'      => [
            'fieldName'    => 'customer',
            'joinColumns'  => [
                [
                    'name'                 => 'customer_id',
                    'unique'               => false,
                    'nullable'             => false,
                    'onDelete'             => null,
                    'columnDefinition'     => null,
                    'referencedColumnName' => 'id',
                ],
            ],
            'targetEntity' => (new \ReflectionClass($entityUser))->getName(),
        ],
        //one-to-many
        'books'         => [
            'fieldName'    => 'books',
            'targetEntity' => (new \ReflectionClass($entityBook))->getName(),
            'mappedBy'     => 'category',
        ],
        //one-to-one
        'owner'         => [
            'fieldName'    => 'owner',
            'targetEntity' => (new \ReflectionClass($entityOwner))->getName(),
            'joinColumns'  => [
                [
                    'name'                 => 'owner_id',
                    'unique'               => true,
                    'nullable'             => false,
                    'onDelete'             => null,
                    'columnDefinition'     => null,
                    'referencedColumnName' => 'id',
                ],
            ],
            'mappedBy'     => null,
        ],
        //one-to-one inverse
        'driverLicence' => [
            'fieldName'    => 'driverLicence',
            'targetEntity' => (new \ReflectionClass($entityDriverLicence))->getName(),
            'joinColumns'  => [],
            'mappedBy'     => 'owner',
        ],
    ];
    $sequenceGenerator = $prophet->prophesize(SequenceGenerator::class);
    $sequenceGenerator->generateId(Argument::any(), Argument::any())->willReturn(1);
    $classMetadata->idGenerator = $sequenceGenerator->reveal();

    $classMetadata = $classMetadata->reveal();
});

test('getIdentifier() should return the primary key name', function () {
    global $classMetadata, $doctrineDatasource, $fakeClass;
    $collection = new Collection($doctrineDatasource, $classMetadata);

    expect($collection->getIdentifier())->toEqual($fakeClass->getName());
});

test('getClassName() should return the primary key name', function () {
    global $classMetadata, $doctrineDatasource, $entityUser;
    $collection = new Collection($doctrineDatasource, $classMetadata);

    expect($collection->getClassName())->toEqual((new \ReflectionClass($entityUser))->getName());
});

test('addFields() should push the entity fields to the collection', function () {
    global $classMetadata, $doctrineDatasource, $fakeClass;
    $collection = new Collection($doctrineDatasource, $classMetadata);

    $fields = [
        'id'    => [
            'fieldName'  => 'id',
            'type'       => 'integer',
            'scale'      => null,
            'length'     => null,
            'unique'     => false,
            'nullable'   => false,
            'precision'  => null,
            'id'         => true,
            'columnName' => 'id',
        ],
        'label' => [
            'fieldName'  => 'label',
            'type'       => 'string',
            'scale'      => null,
            'length'     => 50,
            'unique'     => false,
            'nullable'   => false,
            'precision'  => null,
            'columnName' => 'label',
        ],
    ];
    $collection->addFields($fields);

    expect($collection->getFields()->toArray())->toHaveKeys(['id', 'label']);
});

test('getRelationType() should return a null type on unknown relation', function () {
    global $classMetadata, $doctrineDatasource;

    $entity = new class () {
        public function getShortName()
        {
            return 'foo';
        }

        public function getAttributes()
        {
            return [
                new class () {
                    public function getName()
                    {
                        return 'unknown';
                    }
                },
            ];
        }
    };

    $classMetadata->reflFields = [
        'foo'   => $entity,
    ];

    $classMetadata->associationMappings = [
        'foo'         => [
            'fieldName'    => 'foo',
            'joinTable'    => [],
            'targetEntity' => (new \ReflectionClass($entity))->getName(),
            'mappedBy'     => 'bar',
            'inversedBy'   => null,
        ],
    ];

    $collection = new Collection($doctrineDatasource, $classMetadata);

    expect($collection->getFields())->not()->toHaveKey('foo');
});

test('addOneToOne() should throw when the mappel field doen\'t exist into the related entity', function () {
    global $classMetadata, $doctrineDatasource, $entityDriverLicence;

    $classMetadata->associationMappings = [
        'driverLicence' => [
            'fieldName'    => 'driverLicence',
            'targetEntity' => (new \ReflectionClass($entityDriverLicence))->getName(),
            'joinColumns'  => [],
            'mappedBy'     => 'my-related-field',
        ],
    ];

    expect(fn () => new Collection($doctrineDatasource, $classMetadata))->toThrow(\Exception::class, 'The relation field `my-related-field` does not exist in the entity `'.(new \ReflectionClass($entityDriverLicence))->getName().'`.');
});

test('addManyToMany() should throw when the mappel field doen\'t exist into the related entity', function () {
    global $classMetadata, $doctrineDatasource, $entityUser;

    $classMetadata->associationMappings = [
        'users'         => [
            'fieldName'    => 'users',
            'joinTable'    => [],
            'targetEntity' => (new \ReflectionClass($entityUser))->getName(),
            'mappedBy'     => 'my-related-field',
            'inversedBy'   => null,
        ],
    ];

    expect(fn () => new Collection($doctrineDatasource, $classMetadata))->toThrow(\Exception::class, 'The relation field `my-related-field` does not exist in the entity `'.(new \ReflectionClass($entityUser))->getName().'`.');
});

test('addOneToMany() should throw when the mappel field doen\'t exist into the related entity', function () {
    global $classMetadata, $doctrineDatasource, $entityBook;

    $classMetadata->associationMappings = [
        'books'         => [
            'fieldName'    => 'books',
            'targetEntity' => (new \ReflectionClass($entityBook))->getName(),
            'mappedBy'     => 'my-related-field',
        ],
    ];

    expect(fn () => new Collection($doctrineDatasource, $classMetadata))->toThrow(\Exception::class, 'The relation field `my-related-field` does not exist in the entity `'.(new \ReflectionClass($entityBook))->getName().'`.');
});
