# Slack Deleter
Delete all messages in channel of Slack

# Usage
```
php slackDeleter.php -t <TOKEN> -c <CHANNEL>
```

```
php slackDeleter.php --token <TOKEN> --channel <CHANNEL>
```

## Arguments
* OAuth Access Token
    - Not Bot User OAuth Access Token
    - Require User Token Scopes:
        * channels:history
        * channels:read
        * chat:write
* Channel name of to delete messages
