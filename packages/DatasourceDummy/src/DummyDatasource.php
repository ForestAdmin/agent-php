<?php

namespace ForestAdmin\AgentPHP\DatasourceDummy;

use ForestAdmin\AgentPHP\DatasourceDummy\Collections\Address;
use ForestAdmin\AgentPHP\DatasourceDummy\Collections\Book;
use ForestAdmin\AgentPHP\DatasourceDummy\Collections\Library;
use ForestAdmin\AgentPHP\DatasourceDummy\Collections\LibraryBook;
use ForestAdmin\AgentPHP\DatasourceDummy\Collections\Person;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;

class DummyDatasource extends Datasource
{
    public function __construct()
    {
        parent::__construct();
        $this->addCollection(new Book($this));
        //        $this->addCollection(new Library($this));
        //        $this->addCollection(new LibraryBook($this));
        //        $this->addCollection(new Person($this));
        //        $this->addCollection(new Address($this));
    }
}
