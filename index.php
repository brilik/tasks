<?php
class Debug {

	static function pr( $arr ) {
		echo '<pre>';
		print_r( $arr );
		echo '</pre>';
	}
}
$db_host = 'localhost';
$db_user = 'root';
$db_password = '';
$db_base = 'test_task';
$db_table = 'tasks';
$link = mysqli_connect( $db_host, $db_user, $db_password, $db_base );
function get_tasks() {
	global $link, $db_base, $db_table;
	$sql = "SELECT * FROM {$db_base}.{$db_table}";
	$res = [];
	if ( $result = $link->query( $sql ) ) {
		while ( $row = $result->fetch_assoc() ) {
			array_push( $res, $row );
		}
	}

	return array_reverse($res);
}

function remove_task( int $id ) {
	global $link, $db_table;
	$sql = "DELETE FROM `{$db_table}` WHERE `{$db_table}`.`id` = {$id}";
	$link->query( $sql );
	$link->close();
}

function update_task( int $id, string $content ) {
	global $link, $db_table;
	$content = htmlspecialchars( $content );
	$sql = "UPDATE `{$db_table}` SET `content` = '{$content}' WHERE `{$db_table}`.`id` = {$id};";
	$link->query( $sql );
	$link->close();
	header( "Location: /" );
	exit;
}

if ( isset( $_POST['name'] ) && isset( $_POST['task'] ) ) {
	$name = htmlspecialchars( strip_tags( $_POST['name'] ) );
	$task = htmlspecialchars( trim( $_POST['task'] ) );
	$sql = "INSERT INTO `{$db_table}` (`name`, `content`) VALUES ('{$name}', '{$task}');";
	$link->query( $sql );
	$link->close();
	header( "Location: /" );
	exit;
}
if ( isset( $_POST['task_id'] ) && isset( $_POST['task'] ) ) {
	update_task( $_POST['task_id'], $_POST['task'] );
}
if ( isset( $_GET['remove_task'] ) ) {
	remove_task( $_GET['remove_task'] );
	header( "Location: /" );
	exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Task</title>
</head>
<body>
<main>
    <div class="task-add">
        <form action="/" method="POST">
            <p><input type="text" name="name" placeholder="Enter name" minlength=3 required></p>
            <p><textarea name="task" cols="30" rows="10" placeholder="Enter task" minlength=15 required></textarea></p>
            <p><input type="submit" value="Add"></p>
        </form>
    </div>
    <?php if ( $tasks = get_tasks() ): ?>
        <div class="answer">
		    <?php foreach ( $tasks as $task ): ?>
                <article class="task" id="<?= $task['id']; ?>">
                    <div><span>Name:</span> <?= $task['name']; ?></div>
                    <div>
                        <?php if( isset($_GET['edit_task']) && $_GET['edit_task'] === $task['id'] ): ?>
                            <form action="/" method="post">
                                <p>
                                    <span>Task:</span>
                                    <textarea name="task" class="js-edit-content" cols="40" rows="5"><?= $task['content']; ?></textarea>
                                </p>
                                <div class="btn-edit">
                                    <input type="hidden" name="task_id" value="<?= $_GET['edit_task']; ?>">
                                    <button type="submit" class="btn js-edit-ok">OK</button>
                                    <button class="btn js-edit-cancel">Cancel</button>
                                </div>
                            </form>
                        <?php else: ?>
                            <p><span>Task:</span> <?= $task['content']; ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="answer-actions">
                        <button class="btn js-task__edit">Edit</button>
                        <button class="btn js-task__remove">Х</button>
                    </div>
                </article>
		    <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<style>
    .btn:hover { cursor: pointer; }
    .answer { display: flex; flex-flow: wrap; }
    .task { border: 1px solid dimgrey; height: 150px; width: 300px; padding: 7px; margin: 20px auto; position: relative; }
    .answer > article .answer-actions { position: absolute; top: 10px; right: 10px; }
</style>
<script>
    let task = document.querySelectorAll('.answer > article')

    for (let i = 0; i < task.length; i++) {
        let current = task[i]
        // remove task
        task[i].querySelector('.js-task__remove').onclick = () => {
            let isRemove = confirm('Are you sure to delete the task?')

            if (isRemove)
                document.location = "/?remove_task=" + current.id
        };
        // edit tast
        task[i].querySelector('.js-task__edit').onclick = () => {
            document.location = "/?edit_task=" + current.id
        };
        // cancel edit task
        if( task[i].querySelector('.js-edit-cancel') ){
            task[i].querySelector('.js-edit-cancel').onclick = () => {
                document.location = "/"
            };
        }
    }
</script>
</body>
</html>