<?php
function h($str) { return htmlspecialchars($str, ENT_QUOTES, 'UTF-8'); }
$accounts = [
 [
        'auth_token' => '',//auth token
        'ct0' => '',//ct0
        'bearer' => ''//bearerTOKEN
    ],//以降追加アカウント
];

$logs = [];
$maxTweetsValue = 400; // デフォルト値
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text = $_POST['tweet_text'] ?? '';
    $reply_id = $_POST['reply_id'] ?? '';
    $like_tweet_id = $_POST['like_tweet_id'] ?? '';
    $action_type = $_POST['action_type'] ?? 'tweet';
    $maxTweets = isset($_POST['max_tweets']) ? (int)$_POST['max_tweets'] : 400;
    if ($maxTweets < 1) $maxTweets = 1;
    if ($maxTweets > 500) $maxTweets = 500;
    $maxTweetsValue = $maxTweets;

    $totalTweets = 0;

    while ($totalTweets < $maxTweets) {
        $multiHandle = curl_multi_init();
        $curlHandles = [];
        $accountIndices = [];
        foreach ($accounts as $i => $acc) {
            if ($totalTweets >= $maxTweets) break;

            $bearerToken = $acc['bearer'];
            $csrfToken = $acc['ct0'];
            $cookies = 'auth_token=' . $acc['auth_token'] . '; ct0=' . $csrfToken . ';';

            if ($action_type === 'like' && $like_tweet_id !== '') {
                $data = [
                    'variables' => [
                        'tweet_id' => $like_tweet_id
                    ],
                    'queryId' => 'lI07N6Otwv1PhnEgXILM7A'//queryID変更必須
                ];
                $jsonData = json_encode($data);
                $ch = curl_init('https://x.com/i/api/graphql/lI07N6Otwv1PhnEgXILM7A/FavoriteTweet');//queryID変更必須
            } else {
                $rand = rand(1000, 9999);
                $tweet_text = $text . ' ' . $rand;

                $variables = [
                    'tweet_text' => $tweet_text,
                    'dark_request' => false,
                    'media' => (object)[],
                    'richtext' => false,
                    'semantic_annotation_ids' => [],
                ];
                if ($reply_id !== '') {
                    $variables['reply'] = [
                        'in_reply_to_tweet_id' => $reply_id
                    ];
                }

                $data = [
                    'variables' => $variables,
                    'features' => [
                        'tweetypie_unmention_optimization_enabled' => true,
                        'vibe_api_enabled' => true,
                        'responsive_web_edit_tweet_api_enabled' => true,
                        'graphql_is_translatable_rweb_tweet_enabled' => true,
                        'view_counts_everywhere_enabled' => true,
                        'profile_label_improvements_pcf_label_in_post_enabled' => false,
                        'communities_web_enable_tweet_community_results_fetch' => false,
                        'standardized_nudges_misinfo' => false,
                        'tweet_awards_web_tipping_enabled' => false,
                        'rweb_xchat_enabled' => false,
                        'responsive_web_grok_community_note_auto_translation_is_enabled' => false,
                        'responsive_web_graphql_timeline_navigation_enabled' => false,
                        'responsive_web_grok_analyze_post_followups_enabled' => false,
                        'longform_notetweets_inline_media_enabled' => false,
                        'responsive_web_grok_image_annotation_enabled' => false,
                        'rweb_tipjar_consumption_enabled' => false,
                        'c9s_tweet_anatomy_moderator_badge_enabled' => false,
                        'tweet_with_visibility_results_prefer_gql_limited_actions_policy_enabled' => false,
                        'responsive_web_twitter_article_tweet_consumption_enabled' => false,
                        'responsive_web_grok_imagine_annotation_enabled' => false,
                        'responsive_web_grok_share_attachment_enabled' => false,
                        'verified_phone_label_enabled' => false,
                        'creator_subscriptions_quote_tweet_preview_enabled' => false,
                        'responsive_web_grok_analyze_button_fetch_trends_enabled' => false,
                        'responsive_web_grok_analysis_button_from_backend' => false,
                        'graphql_is_translatable_rweb_tweet_is_translatable_enabled' => false,
                        'articles_preview_enabled' => false,
                        'longform_notetweets_rich_text_read_enabled' => false,
                        'view_counts_everywhere_api_enabled' => false,
                        'longform_notetweets_consumption_enabled' => false,
                        'premium_content_api_read_enabled' => false,
                        'freedom_of_speech_not_reach_fetch_enabled' => false,
                        'payments_enabled' => false,
                        'responsive_web_enhance_cards_enabled' => false,
                        'responsive_web_grok_show_grok_translated_post' => false,
                        'responsive_web_jetfuel_frame' => false,
                        'responsive_web_graphql_skip_user_profile_image_extensions_enabled' => false,
                    ],
                    'queryId' => 'mGOM24dT4fPg08ByvrpP2A'
                ];
                $jsonData = json_encode($data);
                $ch = curl_init('https://x.com/i/api/graphql/mGOM24dT4fPg08ByvrpP2A/CreateTweet');
            }
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'authorization: ' . $bearerToken,
                'x-csrf-token: ' . $csrfToken,
                'content-type: application/json',
                'cookie: ' . $cookies,
                'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0',
                'referer: https://x.com/compose/post'
            ]);

            curl_multi_add_handle($multiHandle, $ch);
            $curlHandles[] = $ch;
            $accountIndices[] = $i;

            $totalTweets++;
        }
        do {
            $status = curl_multi_exec($multiHandle, $active);
            curl_multi_select($multiHandle);
        } while ($active && $status == CURLM_OK);

        foreach ($curlHandles as $idx => $ch) {
            $response = curl_multi_getcontent($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            $curlErrno = curl_errno($ch);
            curl_multi_remove_handle($multiHandle, $ch);
            curl_close($ch);

            $now = date('H:i:s');
            $accountNum = $accountIndices[$idx] + 1;
            $responseData = json_decode($response, true);
            $errorMessage = '';
            if ($curlErrno !== 0) {
                $errorMessage = "cURLエラー ({$curlErrno}): " . h($curlError);
            } elseif (isset($responseData['errors']) && !empty($responseData['errors'])) {
                $errorMessage = 'APIエラー: ' . json_encode($responseData['errors'], JSON_UNESCAPED_UNICODE);
            } elseif (isset($responseData['data']) && isset($responseData['data']['favorite_tweet'])) {
                $errorMessage = 'APIレスポンス: ' . json_encode($responseData['data'], JSON_UNESCAPED_UNICODE);
            } elseif (isset($responseData['data']) && isset($responseData['data']['create_tweet'])) {
                $errorMessage = 'APIレスポンス: ' . json_encode($responseData['data'], JSON_UNESCAPED_UNICODE);
            } else {
                $errorMessage = 'レスポンス: ' . h($response);
            }
            
            if ($httpCode == 200) {
                if ($action_type === 'like') {
                    $logs[] = "<div class='mb-1'><span class='text-gray-400'>[{$now}]</span> <span class='text-green-400'>アカウント{$accountNum}:</span> <span class='text-green-500'>いいね成功！</span> " . $errorMessage . "</div>";
                } else {
                    $logs[] = "<div class='mb-1'><span class='text-gray-400'>[{$now}]</span> <span class='text-green-400'>アカウント{$accountNum}:</span> <span class='text-green-500'>ツイート成功！</span> " . $errorMessage . "</div>";
                }
            } else {
                if ($action_type === 'like') {
                    $logs[] = "<div class='mb-1'><span class='text-gray-400'>[{$now}]</span> <span class='text-green-400'>アカウント{$accountNum}:</span> <span class='text-red-500'>いいね失敗 (HTTP {$httpCode}):</span> " . $errorMessage . "</div>";
                } else {
                    $logs[] = "<div class='mb-1'><span class='text-gray-400'>[{$now}]</span> <span class='text-green-400'>アカウント{$accountNum}:</span> <span class='text-red-500'>ツイート失敗 (HTTP {$httpCode}):</span> " . $errorMessage . "</div>";
                }
            }
        }

        curl_multi_close($multiHandle);

        if ($totalTweets < $maxTweets) {
            usleep(500000);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Xツイートツール</title>
    <script src="https://cdn.tailwindcss.com"></script>
      <style>
    input[type="range"] {
      -webkit-appearance: none;
      appearance: none;
      width: 100%;
      height: 6px;
      background: linear-gradient(to right, #2bff00ff, #023300ff);
      border-radius: 9999px;
      outline: none;
    }
    input[type="range"]::-webkit-slider-thumb {
      -webkit-appearance: none;
      appearance: none;
      width: 20px;
      height: 20px;
      border-radius: 9999px;
      background: white;
      border: 3px solid #10691fff;
      box-shadow: 0 0 6px rgba(0,0,0,0.3);
      cursor: pointer;
      transition: transform 0.2s ease;
    }
    input[type="range"]::-webkit-slider-thumb:hover {
      transform: scale(1.2);
    }
    input[type="range"]::-moz-range-thumb {
      width: 20px;
      height: 20px;
      border-radius: 9999px;
      background: white;
      border: 3px solid #3b82f6;
      box-shadow: 0 0 6px rgba(0,0,0,0.3);
      cursor: pointer;
      transition: transform 0.2s ease;
    }
    input[type="range"]::-moz-range-thumb:hover {
      transform: scale(1.2);
    }
  </style>
</head>
<body class="bg-gray-900 min-h-screen flex flex-col items-center justify-start py-10">
    <div class="w-full max-w-xl">
        <form id="tweetForm" method="post" class="bg-gray-800 p-6 rounded-lg shadow-lg mb-6">
            <div class="mb-4">
                <label class="block text-green-400 font-mono mb-2">機能選択:</label>
                <div class="flex space-x-4">
                    <label class="flex items-center">
                        <input type="radio" name="action_type" value="tweet" <?php echo ($_POST['action_type'] ?? 'tweet') === 'tweet' ? 'checked' : ''; ?> class="mr-2">
                        <span class="text-green-300">ツイート</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="action_type" value="like" <?php echo ($_POST['action_type'] ?? '') === 'like' ? 'checked' : ''; ?> class="mr-2">
                        <span class="text-green-300">いいね</span>
                    </label>
                </div>
            </div>

            <div id="tweetFields">
                <label class="block text-green-400 font-mono mb-2">ツイート内容:</label>
                <input type="text" name="tweet_text" value="<?php echo h($_POST['tweet_text'] ?? 'それなｗ'); ?>" class="w-full mb-4 px-3 py-2 rounded bg-gray-700 text-green-300 font-mono focus:outline-none focus:ring-2 focus:ring-green-400">

                <label class="block text-green-400 font-mono mb-2">返信先ツイートID（空欄なら通常投稿）:</label>
                <input type="text" name="reply_id" value="<?php echo h($_POST['reply_id'] ?? ''); ?>" class="w-full mb-4 px-3 py-2 rounded bg-gray-700 text-green-300 font-mono focus:outline-none focus:ring-2 focus:ring-green-400">
            </div>

            <div id="likeFields" style="display: none;">
                <label class="block text-green-400 font-mono mb-2">いいねするツイートID:</label>
                <input type="text" name="like_tweet_id" value="<?php echo h($_POST['like_tweet_id'] ?? ''); ?>" class="w-full mb-4 px-3 py-2 rounded bg-gray-700 text-green-300 font-mono focus:outline-none focus:ring-2 focus:ring-green-400">
            </div>

            <label class="block text-green-400 font-mono mb-2">実行回数 (1～500): <span id="tweetCountLabel" class="text-green-300"><?php echo h($maxTweetsValue); ?></span></label>
            <input type="range" name="max_tweets" min="1" max="500" value="<?php echo h($maxTweetsValue); ?>" id="tweetCount" class="w-full mb-4" />

            <button type="submit" class="w-full py-2 bg-green-600 hover:bg-green-700 text-white font-bold rounded transition" id="submitButton">ツイート開始</button>
        </form>
        <div class="bg-black rounded-lg p-6 shadow-lg font-mono text-green-400 text-sm" style="min-height:300px;max-height:400px;overflow-y:auto;" id="cmdLog">
            <?php
            if (!empty($logs)) {
                foreach ($logs as $log) echo $log;
            } else {
                echo "<span class='text-gray-500'>[INFO] ツイートログがここに表示されます</span>";
            }
            ?>
        </div>
    </div>
    <script>
        const tweetCountInput = document.getElementById('tweetCount');
        const tweetCountLabel = document.getElementById('tweetCountLabel');
        const actionTypeRadios = document.querySelectorAll('input[name="action_type"]');
        const tweetFields = document.getElementById('tweetFields');
        const likeFields = document.getElementById('likeFields');
        const submitButton = document.getElementById('submitButton');

        tweetCountInput.addEventListener('input', () => {
            tweetCountLabel.textContent = tweetCountInput.value;
        });

        actionTypeRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                if (radio.value === 'like') {
                    tweetFields.style.display = 'none';
                    likeFields.style.display = 'block';
                    submitButton.textContent = 'いいね開始';
                } else {
                    tweetFields.style.display = 'block';
                    likeFields.style.display = 'none';
                    submitButton.textContent = 'ツイート開始';
                }
            });
        });
        const checkedRadio = document.querySelector('input[name="action_type"]:checked');
        if (checkedRadio && checkedRadio.value === 'like') {
            tweetFields.style.display = 'none';
            likeFields.style.display = 'block';
            submitButton.textContent = 'いいね開始';
        }
    </script>
</body>
</html>
