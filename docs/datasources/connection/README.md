After doing the quickstart, you should have a development project which is up and running and connected to your main data storage system.

However, you can plug as many data sources as you want into the same agent.

## What can I connect to?

Forest Admin collections map to any of those concepts:

- Database collections/tables
- ORM collections
- Endpoints on SaaS providers (by writing a custom data source)
- Endpoints on your own API (by writing a custom data source)

## Example

In this example, we import tables from a PostgreSQL, MariaDB, and Mongo database into Forest Admin.

```php
<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\DatasourceSql\SqlDataSource;
use ForestAdmin\AgentPHP\DatasourceDoctrine\DoctrineDatasource;

$agent = new AgentFactory($options);
$agent->addDatasource(new SqlDataSource('postgres://user:pass@localhost:5432/mySchema'));
$agent->addDatasource(new DoctrineDatasource(new EntityManager()));
$agent->build();
```
