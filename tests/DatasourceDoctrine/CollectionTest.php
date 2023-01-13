<?php

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Id\SequenceGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use ForestAdmin\AgentPHP\Agent\Utils\QueryConverter;
use ForestAdmin\AgentPHP\DatasourceDoctrine\Collection;
use ForestAdmin\AgentPHP\DatasourceDoctrine\DoctrineDatasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use Illuminate\Database\Query\Builder;
use Prophecy\Argument;
use Prophecy\Prophet;

beforeEach(closure: function () {
    global $classMetadata, $doctrineDatasource, $fakeClass, $entityUser;

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
    $schemaTable->getColumns()->willReturn([
        //'category_id' => new Column('category_id', new IntegerType()),
        //'user_id'     => new Column('category_id', new IntegerType()),
    ]);
    $schemaTable->getForeignKeys()->willReturn([
        'fk_123' => new ForeignKeyConstraint(['user_id'], 'users', ['id']),
    ]);
    $schemaTable->getPrimaryKey()->willReturn(new Doctrine\DBAL\Schema\Index('id', []));

    $abstractSchemaManager = $prophet->prophesize(AbstractSchemaManager::class);
    $abstractSchemaManager->introspectTable(Argument::type('string'))->willReturn($schemaTable->reveal());

    $connexion = $prophet->prophesize(Connection::class);
    $connexion->createSchemaManager()->willReturn($abstractSchemaManager->reveal());

    $entityManager = $prophet->prophesize(EntityManager::class);
    $entityManager->getMetadataFactory()
        ->willReturn($metadataFactory->reveal());
    $entityManager->getConnection()->willReturn($connexion->reveal());

    $doctrineDatasource = $prophet->prophesize(DoctrineDatasource::class);
    $doctrineDatasource
        ->getEntityManager()
        ->willReturn($entityManager->reveal());

    $doctrineDatasource->getCollections()->willReturn(collect());
//    $doctrineDatasource->getCollections()->willReturn(collect([
//        'CategoryUser' => new class () {
//            public function getName()
//            {
//                return 'categories_users';
//            }
//        },
//    ]));

    //$doctrineDatasource->addCollection(Argument::any())->willReturn();

    $doctrineDatasource = $doctrineDatasource->reveal();

    $classMetadata = $prophet->prophesize(ClassMetadata::class);
    $classMetadata->fieldNames = [
        'id' => 'id',
    ];
    $classMetadata->reflClass = new \ReflectionClass($entityUser);
    $classMetadata->getName()->willReturn('User');
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
    global $classMetadata, $doctrineDatasource;

    $collection = new Collection($doctrineDatasource, $classMetadata);

    expect($collection->getClassName())->toEqual('User');
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

test('create() should ', function (Caller $caller) {
    global $classMetadata, $doctrineDatasource, $entityUser;
    $collection = new Collection($doctrineDatasource, $classMetadata);


    $prophet = new Prophet();
    $builder = $prophet->prophesize(Builder::class);
    $builder->insertGetId(Argument::type('array'))->willReturn(1);
    $queryConverter = $prophet->prophesize(QueryConverter::class);
    // $queryConverter->query = $builder->reveal();

    $data = [
        'attributes' => [
            'id'    => 1,
            'label' => 'Foo',
        ],
        //        'relationships' => [
        //            'category' => [
        //                'data' => [
        //                    'type' => 'Categories',
        //                    'id'   => '20',
        //                ],
        //            ],
        //        ],
        'type'       => 'class@anonymous\x00/Users/matthieuvideaud/Sites/agents/in-app/agent-php/tests/DatasourceDoctrine/CollectionTest.php:22$1dc',
    ];
    //(new \ReflectionClass($entityUser))->getName()
    $collection->create($caller, $data);

    //expect($collection->getFields()->toArray())->toHaveKeys(['id', 'label']);
})->with('caller');
