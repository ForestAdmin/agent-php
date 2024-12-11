<?php

use ForestAdmin\AgentPHP\Agent\Utils\QueryValidator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

it('validates a correct SELECT query', function () {
    $query = 'SELECT * FROM users;';
    expect(QueryValidator::valid($query))->toBeTrue();
});

it('allows a query with a WHERE clause containing parentheses', function () {
    $query = 'SELECT * FROM users WHERE (id > 1 AND name = "John");';
    expect(QueryValidator::valid($query))->toBeTrue();
});

it('allows balanced parentheses in subqueries', function () {
    $query = 'SELECT * FROM (SELECT id FROM users) AS subquery;';
    expect(QueryValidator::valid($query))->toBeTrue();
});

it('allows a query with a subquery using the IN clause', function () {
    $query = 'SELECT id, name FROM users WHERE id IN (SELECT user_id FROM orders WHERE total > 100);';
    expect(QueryValidator::valid($query))->toBeTrue();
});

it('allows a query without a semicolon when semicolon is not required', function () {
    $query = 'SELECT name FROM users';
    expect(QueryValidator::valid($query))->toBeTrue();
});

it('does not raise an error for a semicolon inside a string in the WHERE clause', function () {
    $query = 'SELECT * FROM users WHERE name = "test;";';
    expect(QueryValidator::valid($query))->toBeTrue();
});

it('does not raise an error for a parenthesis inside a string in the WHERE clause', function () {
    $query = 'SELECT * FROM users WHERE name = "(test)";';
    expect(QueryValidator::valid($query))->toBeTrue();
});

it('allows a query with a lowercase SELECT', function () {
    $query = "select * from users WHERE username = 'admin';";
    expect(QueryValidator::valid($query))->toBeTrue();
});



it('throws an exception for empty query', function () {
    $query = '';
    QueryValidator::valid($query);
})->throws(ForestException::class, 'Query cannot be empty.');

it('throws an exception for non-SELECT queries', function () {
    $query = 'UPDATE users SET name = "John" WHERE id = 1;';
    QueryValidator::valid($query);
})->throws(ForestException::class, 'Only SELECT queries are allowed.');

it('throws an exception for multiple queries', function () {
    $query = 'SELECT * FROM users; SELECT * FROM orders;';
    QueryValidator::valid($query);
})->throws(ForestException::class, 'Only one query is allowed.');

it('throws an exception for unbalanced parentheses outside WHERE clause', function () {
    $query = 'SELECT (id, name FROM users WHERE (id > 1);';
    QueryValidator::valid($query);
})->throws(ForestException::class, 'The query contains unbalanced parentheses.');

it('throws an exception for a semicolon not at the end of the query', function () {
    $query = 'SELECT * FROM users; WHERE id > 1';
    QueryValidator::valid($query);
})->throws(ForestException::class, 'Semicolon must only appear as the last character in the query.');

it('throws an exception for forbidden keywords even inside subqueries', function () {
    $query = 'SELECT * FROM users WHERE id IN (DROP TABLE users);';
    QueryValidator::valid($query);
})->throws(ForestException::class, 'The query contains forbidden keyword: DROP.');

it('throws an exception for unbalanced parentheses in subqueries', function () {
    $query = 'SELECT * FROM (SELECT id, name FROM users WHERE id > 1;';
    QueryValidator::valid($query);
})->throws(ForestException::class, 'The query contains unbalanced parentheses.');

it('throws an exception for an OR-based injection', function () {
    $query = "SELECT * FROM users WHERE username = 'admin' OR 1=1;";
    QueryValidator::valid($query);
})->throws(ForestException::class, 'Potential SQL injection detected.');
