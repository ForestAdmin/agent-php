<?php

namespace ForestAdmin\AgentPHP\Agent\Utils;

use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

class QueryValidator
{
    private const FORBIDDEN_KEYWORDS = ['DROP', 'DELETE', 'INSERT', 'UPDATE', 'ALTER'];

    private const INJECTION_PATTERNS = [
        '/\bOR\s+1=1\b/i', // OR 1=1
    ];

    public static function valid(string $query): bool
    {
        $query = trim($query);

        if (empty($query)) {
            throw new ForestException('Query cannot be empty.');
        }

        $sanitizedQuery = self::removeContentInsideStrings($query);

        self::checkSelectOnly($sanitizedQuery);
        self::checkSemicolonPlacement($sanitizedQuery);
        self::checkForbiddenKeywords($sanitizedQuery);
        self::checkUnbalancedParentheses($sanitizedQuery);
        self::checkInjectionPatterns($sanitizedQuery);

        return true;
    }

    private static function checkSelectOnly(string $query): void
    {
        if (! str_starts_with(strtoupper($query), 'SELECT')) {
            throw new ForestException('Only SELECT queries are allowed.');
        }
    }

    private static function checkSemicolonPlacement(string $query): void
    {
        $semicolonCount = substr_count($query, ';');

        if ($semicolonCount > 1) {
            throw new ForestException('Only one query is allowed.');
        }

        if ($semicolonCount === 1 && substr(trim($query), -1) !== ';') {
            throw new ForestException('Semicolon must only appear as the last character in the query.');
        }
    }

    private static function checkForbiddenKeywords(string $query): void
    {
        foreach (self::FORBIDDEN_KEYWORDS as $keyword) {
            if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $query)) {
                throw new ForestException("The query contains forbidden keyword: $keyword.");
            }
        }
    }

    private static function checkUnbalancedParentheses(string $query): void
    {
        $openCount = substr_count($query, '(');
        $closeCount = substr_count($query, ')');

        if ($openCount !== $closeCount) {
            throw new ForestException('The query contains unbalanced parentheses.');
        }
    }

    private static function checkInjectionPatterns(string $query): void
    {
        foreach (self::INJECTION_PATTERNS as $pattern) {
            if (preg_match($pattern, $query)) {
                throw new ForestException('Potential SQL injection detected.');
            }
        }
    }

    private static function removeContentInsideStrings(string $query): string
    {
        // Remove content inside single and double quotes
        return preg_replace(["/'(?:[^']|\\\\')*'/", '/"(?:[^"]|\\\\")*"/'], '', $query) ?? $query;
    }
}
