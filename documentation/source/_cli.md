## Quiet Mode

To make the output much simpler, use the `--quite` flag. This will hide the assertions and reduce the output to simply pass/fail.

    ./check_pages failing_tests_runner.php --quiet

## Filter

Use the `--filter` parameter combined with a suite name to limit the runner to a single suite. This is faster than editing your runner file.

    ./check_pages runner.php --filter=page_header

## Troubleshooting

Try using the `--response` to see the response source code as well.

    ./check_pages failing_tests_runner.php --response
