<?php

$option = getopt('t:c:h', ['token:', 'channel:', 'help']);

$slackApiKey = isset($option['t']) ? $option['t'] : @$option['token'];
$channel = isset($option['c']) ? $option['c'] : @$option['channel'];

if(isset($option['h']) OR isset($option['help'])){
    help();
    exit(0);
}

if(!isset($slackApiKey) OR !isset($channel)){
    echo "Invalid argument\n\n";
    help();
    exit(1);
}

$channel = str_replace('#', '', $channel);

$channelId = getChannelId($slackApiKey, $channel);
$tsList = getTsList($slackApiKey, $channelId);

$progress = 0;
$tsCount = count($tsList);
echo "${progress}/${tsCount}";
foreach($tsList as $v){
    deleteChat($slackApiKey, $channelId, $v);
    $progress++;
    echo "\r";
    echo "${progress}/${tsCount}";
}
echo "\n";
exit(0);


function getChannelId($slackApiKey, $channelStr){
    $url = "https://slack.com/api/channels.list?token=${slackApiKey}";
    $content = file_get_contents($url);
    if($content === false){
        echo "Failed to get response from Slack Api\n";
        exit(1);
    }
    $json = json_decode($content, true);
    echoErr($json);

    $channels = [];
    foreach($json['channels'] as $v){
        $channels[$v['name']] = $v['id'];
    }

    if(isset($channels[$channelStr])){
        return $channels[$channelStr];
    }else{
        echo "Failed to get channel id from string\n";
        exit(1);
    }
}

function getTsList($slackApiKey, $channelId){
    $tsList = [];
    $latest = '';
    $hasMore = true;
    while($hasMore){
        $url = "https://slack.com/api/channels.history?token=${slackApiKey}&channel=${channelId}&latest=${latest}";
        $content = file_get_contents($url);
        if($content === false){
            echo "Failed to get response from Slack Api\n";
            exit(1);
        }
        $json = json_decode($content, true);
        echoErr($json);
        $hasMore = $json['has_more'];
        foreach($json['messages'] as $v){
            $tsList[] = $v['ts'];
        }
        if(count($tsList) < 1){
            return $tsList;
        }
        $latest = $tsList[count($tsList) - 1];
    }
    return $tsList;
}

function deleteChat($slackApiKey, $channelId, $ts){
    $data = [
        'token' => $slackApiKey,
        'channel' => $channelId,
        'ts' => $ts
    ];
    $url = 'https://slack.com/api/chat.delete';
    $options = [
        'http' => [
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    $content = @file_get_contents($url, false, stream_context_create($options));
    if($content === false){
        echo "Failed to delete message\n";
        echo "ts is ${ts}\n\n";
        return;
    }
    $json = json_decode($content, true);
    echoErr($json, false);
}

function echoErr($json, $isExit = true){
    if($json['ok'] === false){
        echo "Error: {$json['error']}\n";
        if($isExit){
            exit(1);
        }
    }
}

function help(){
    echo "Slack Deleter Help\n";
    echo "Delete all messages in channel of Slack\n";
    echo "\n";
    echo "Requirements:\n";
    echo "\t-t, --token\tSlack api token\n";
    echo "\t-c, --channel\tChannel name of to delete messages\n";
}

