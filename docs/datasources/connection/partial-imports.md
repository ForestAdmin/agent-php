You may not want to import all collections from a data source.

This can be achieved by providing a whitelist or a blacklist in the options of the `$agent->addDataSource` method.

```php
<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\DatasourceSql\SqlDataSource;

$agent = new AgentFactory($options);
$sqlDataSource = new SqlDataSource('postgres://user:pass@localhost:5432/mySchema');
$agent->addDatasource($sqlDataSource, ['exclude' => ['User']]);
$agent->build();
```
