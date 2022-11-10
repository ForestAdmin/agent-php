When importing collections to an admin panel, you may encounter naming collisions.

You can tackle them by renaming the collection that is causing issues.

Don't worry if you leave naming collisions, your development agent will warn you while starting.


TODO UPDATE WHEN SQLDATASOURCE IS IMPLEMENTED
```php
<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\DatasourceSql\SqlDataSource;

$agent = new AgentFactory($options);
$sqlDataSource = new SqlDataSource('postgres://user:pass@localhost:5432/mySchema');
$agent->addDatasource($sqlDataSource, ['rename' => ['Product' => 'Package']]);
$agent->build();
```
