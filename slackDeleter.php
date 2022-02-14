<?php

declare(strict_types=1);

$option = getopt('t:c:h', ['token:', 'channel:', 'help']);

$oauthToken = isset($option['t']) ? $option['t'] : @$option['token'];
$channelName = isset($option['c']) ? $option['c'] : @$option['channel'];

if (isset($option['h']) || isset($option['help'])) {
    help();
    exit(0);
}

if (!isset($oauthToken) || !isset($channelName)) {
    echo "Invalid argument\n\n";
    help();
    exit(1);
}

$channelName = str_replace('#', '', $channelName);

$channelId = (function () use ($oauthToken, $channelName) {
    $url = "https://slack.com/api/conversations.list?token=${oauthToken}";
    $json = json_decode(file_get_contents($url), true);
    foreach ($json['channels'] as $channel) {
        if ($channel['name'] === $channelName) {
            return $channel['id'];
        }
    }
})();
if ($channelId === null) {
    echo "Can't find channel id\n";
    exit(1);
}

$deleteTs = (function () use ($oauthToken, $channelId) {
    $cursor = '';
    while (true) {
        $url = "https://slack.com/api/conversations.history?token=${oauthToken}&channel=${channelId}&limit=1000&cursor=${cursor}";
        $json = json_decode(file_get_contents($url), true);
        $cursor = $json['response_metadata']['next_cursor'] ?? '';
        foreach ($json['messages'] as $message) {
            $tss[] = $message['ts'];
        }
        if (!$json['has_more']) {
            break;
        }
    }
    arsort($tss);
    return $tss;
})();

$progress = 0;
$tsCount = count($deleteTs);
echo "${progress}/${tsCount}";
foreach ($deleteTs as $ts) {
    $url = "https://slack.com/api/chat.delete?token=${oauthToken}&channel=${channelId}&ts=${ts}";
    $options = ['http' => ['method' => 'POST']];
    file_get_contents($url, false, stream_context_create($options));
    $progress++;
    echo "\r";
    echo "${progress}/${tsCount}";
}
echo "\n";

function help()
{
    echo "Slack Deleter Help\n";
    echo "Delete all messages in channel of Slack\n";
    echo "\n";
    echo "Requirements:\n";
    echo "  -t, --token\n";
    echo "    Slack OAuth Access Token\n";
    echo "      Not Bot User OAuth Access Token\n";
    echo "    Require User Token Scopes:\n";
    echo "      * channels:history\n";
    echo "      * channels:read\n";
    echo "      * chat:write\n";
    echo "  -c, --channel\n";
    echo "    Channel name of to delete messages\n";
}
