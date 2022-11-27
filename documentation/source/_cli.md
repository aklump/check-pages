## Filter

Use the `--filter` parameter combined with a suite name to limit the runner to a single suite. This is faster than editing your runner file.

    ./check_pages runner.php --filter=page_header

Or the shorthand
    
    ./check_pages runner.php -f page_header

Combine more than one filter value for an OR selection

    ./check_pages runner.php -f page_header -f rss

## Troubleshooting

Try using the `--response` to see the response source code as well.

    ./check_pages failing_tests_runner.php --response
