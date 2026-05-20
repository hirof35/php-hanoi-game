<?php
session_start();

// 1. 基本設定
$total_disks = 3;  // 円盤の数
$max_moves = 15;   // ゲームオーバーになる最大手数（自由に調整してください）

// 最初のリセット処理、または初期起動時
if (isset($_POST['to_title']) || !isset($_SESSION['screen'])) {
    $_SESSION['screen'] = 'title'; // 最初の画面はタイトル
}

// ゲームの開始（初期化）
if (isset($_POST['start']) || isset($_POST['reset'])) {
    $_SESSION['towers'] = [
        'A' => range($total_disks, 1),
        'B' => [],
        'C' => []
    ];
    $_SESSION['selected'] = null;
    $_SESSION['moves'] = 0;
    $_SESSION['message'] = "ゲームスタート！円盤を選択してください。";
    $_SESSION['screen'] = 'game'; // ゲーム画面へ移動
}

// 2. プレイヤーのアクション（ゲーム画面のときのみ処理）
if ($_SESSION['screen'] === 'game' && isset($_POST['rod'])) {
    $clicked_rod = $_POST['rod'];

    if ($_SESSION['selected'] === null) {
        // --- 1回目のクリック: 移動元を選択 ---
        if (!empty($_SESSION['towers'][$clicked_rod])) {
            $_SESSION['selected'] = $clicked_rod;
            $_SESSION['message'] = "棒 【{$clicked_rod}】 の一番上の円盤を選択中。移動先を選んでください。";
        } else {
            $_SESSION['message'] = "選択した棒 【{$clicked_rod}】 には円盤がありません。";
        }
    } else {
        // --- 2回目のクリック: 移動先を選択 ---
        $from = $_SESSION['selected'];
        $to = $clicked_rod;
        $_SESSION['selected'] = null; // 選択状態をリセット

        if ($from !== $to) {
            $moving_disk = end($_SESSION['towers'][$from]);
            $target_top_disk = empty($_SESSION['towers'][$to]) ? 999 : end($_SESSION['towers'][$to]);

            // ルール判定
            if ($moving_disk < $target_top_disk) {
                array_pop($_SESSION['towers'][$from]);
                $_SESSION['towers'][$to][] = $moving_disk;
                $_SESSION['moves']++;
                $_SESSION['message'] = "円盤を 【{$from}】 から 【{$to}】 へ移動しました。";

                // ① クリア判定（棒Cにすべての円盤が移動したらクリア）
                if (count($_SESSION['towers']['C']) === $total_disks) {
                    $_SESSION['screen'] = 'clear';
                }
                // ② ゲームオーバー判定（最大手数を越えたらゲームオーバー）
                elseif ($_SESSION['moves'] >= $max_moves) {
                    $_SESSION['screen'] = 'gameover';
                }
            } else {
                $_SESSION['message'] = "❌ ルール違反：小さい円盤の上に大きい円盤は置けません！";
            }
        } else {
            $_SESSION['message'] = "キャンセルしました。";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>PHP ハノイの塔ゲーム</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; background: #f0f0f0; padding-top: 50px; }
        .screen { background: white; max-width: 650px; margin: 0 auto; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .game-container { display: flex; justify-content: center; margin: 30px auto; width: 600px; }
        .rod-button { background: #e0e0e0; border: 3px solid #888; border-radius: 8px; width: 150px; min-height: 200px; margin: 0 15px; cursor: pointer; display: flex; flex-direction: column-reverse; align-items: center; padding-bottom: 10px; transition: 0.2s; }
        .rod-button:hover { background: #d0d0d0; }
        .selected { border-color: #ff4500; background: #ffe4e1; }
        .disk { height: 30px; margin: 2px 0; border-radius: 5px; color: white; font-weight: bold; line-height: 30px; text-align: center; box-shadow: 1px 1px 3px rgba(0,0,0,0.3); }
        .disk-1 { width: 60px; background: #ff4757; }
        .disk-2 { width: 100px; background: #2ed573; }
        .disk-3 { width: 140px; background: #1e90ff; }
        .info { margin: 20px; font-size: 1.1em; font-weight: bold; color: #333; }
        .btn { padding: 12px 24px; font-size: 1.1em; cursor: pointer; border: none; border-radius: 5px; background: #1e90ff; color: white; font-weight: bold; margin: 10px; transition: 0.2s; }
        .btn:hover { background: #1070e0; }
        .btn-danger { background: #ff4757; }
        .btn-danger:hover { background: #e03040; }
        h1 { color: #2f3542; margin-bottom: 20px; }
        .result-score { font-size: 1.5em; color: #ff4757; margin: 20px 0; font-weight: bold; }
    </style>
</head>
<body>

<div class="screen">

    <?php if ($_SESSION['screen'] === 'title'): ?>
        <!-- ================= タイトル画面 ================= -->
        <h1>🏰 ハノイの塔 ゲーム 🏰</h1>
        <p>すべての円盤を一番右の棒に移動させればクリア！<br>ただし、小さい円盤の上に大きな円盤を置くことはできません。</p>
        <p>制限手数: <strong><?php echo $max_moves; ?>手以内</strong></p>
        
        <form method="POST" action="">
            <button type="submit" name="start" class="btn">ゲームを始める</button>
        </form>


    <?php elseif ($_SESSION['screen'] === 'game'): ?>
        <!-- ================= ゲーム画面 ================= -->
        <h1>🏰 ハノイの塔 🏰</h1>
        
        <div class="info">
            <p>現在の移動手数: <?php echo $_SESSION['moves']; ?> / <?php echo $max_moves; ?> 手</p>
            <p style="color: #ff4500; min-height: 1.2em;"><?php echo $_SESSION['message']; ?></p>
        </div>

        <form method="POST" action="">
            <div class="game-container">
                <?php foreach ($_SESSION['towers'] as $rod_name => $disks): ?>
                    <?php $isSelected = ($_SESSION['selected'] === $rod_name) ? 'selected' : ''; ?>
                    <button type="submit" name="rod" value="<?php echo $rod_name; ?>" class="rod-button <?php echo $isSelected; ?>">
                        <strong>棒 <?php echo $rod_name; ?></strong>
                        <?php foreach ($disks as $disk): ?>
                            <div class="disk disk-<?php echo $disk; ?>"><?php echo $disk; ?></div>
                        <?php endforeach; ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <button type="submit" name="reset" class="btn btn-danger">最初からやり直す</button>
            <button type="submit" name="to_title" class="btn" style="background:#747d8c;">タイトルへ戻る</button>
        </form>


    <?php elseif ($_SESSION['screen'] === 'clear'): ?>
        <!-- ================= ゲームクリア画面 ================= -->
        <h1>🎉 GAME CLEAR! 🎉</h1>
        <p>見事にすべての円盤を移動させました！おめでとうございます！</p>
        
        <div class="result-score">
            かかった手数: <?php echo $_SESSION['moves']; ?> 手
        </div>

        <form method="POST" action="">
            <button type="submit" name="start" class="btn">もう一度遊ぶ</button>
            <button type="submit" name="to_title" class="btn" style="background:#747d8c;">タイトルへ戻る</button>
        </form>


    <?php elseif ($_SESSION['screen'] === 'gameover'): ?>
        <!-- ================= ゲームオーバー画面 ================= -->
        <h1>💀 GAME OVER 💀</h1>
        <p>設定された制限手数（<?php echo $max_moves; ?>手）を超えてしまいました……。</p>
        
        <div class="result-score" style="color: #57606f;">
            無念……！
        </div>

        <form method="POST" action="">
            <button type="submit" name="start" class="btn">リベンジする</button>
            <button type="submit" name="to_title" class="btn" style="background:#747d8c;">タイトルへ戻る</button>
        </form>

    <?php endif; ?>

</div>

</body>
</html>
