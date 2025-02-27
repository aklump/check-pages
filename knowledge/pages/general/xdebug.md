<!--
id: xdebug
tags: ''
-->

# Debugging While Running Tests

## XDebug 3
https://xdebug.org/docs/all_settings#discover_client_host

From your logs: 192.168.50.1 (your physical machine IP, where PhpStorm is running -- as seen from VM) vs 127.0.0.1 (your local VM). P.S. You should have mentioned that it's an remote debug / running on VM. For local it would have made no difference but for remote


### Checklist

1. Execute `lyb`
2. Make sure `xdebug_disable_from_host` is not activated in _bin/run_check_pages_.
3. Make sure PhpStorm is listening.
4. Check the Xdebug settings to match:
   ```
   xdebug.client_host = host.docker.internal
   xdebug.start_with_request = yes
   xdebug.discover_client_host = true
   ```
