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
$db_table_tasks = 'tasks';
$db_table_users = 'users';
$link = mysqli_connect( $db_host, $db_user, $db_password, $db_base );
function get_tasks() {
	global $link, $db_base, $db_table_tasks;
	$sql = "SELECT * FROM {$db_base}.{$db_table_tasks}";
	$res = [];
	if ( $result = $link->query( $sql ) ) {
		while ( $row = $result->fetch_assoc() ) {
			array_push( $res, $row );
		}
	}

	return array_reverse($res);
}

function remove_task( int $id ) {
	global $link, $db_table_tasks;
	$sql = "DELETE FROM `{$db_table_tasks}` WHERE `{$db_table_tasks}`.`id` = {$id}";
	$link->query( $sql );
	$link->close();
}

function update_task( int $id, string $content ) {
	global $link, $db_table_tasks;
	$content = htmlspecialchars( $content );
	$sql = "UPDATE `{$db_table_tasks}` SET `content` = '{$content}' WHERE `{$db_table_tasks}`.`id` = {$id};";
	$link->query( $sql );
	$link->close();
	header( "Location: /" );
	exit;
}

function get_users() {
	global $link, $db_base, $db_table_users;
	$sql = "SELECT * FROM {$db_base}.{$db_table_users}";
	$res = [];
	if ( $result = $link->query( $sql ) ) {
		while ( $row = $result->fetch_assoc() ) {
			array_push( $res, $row );
		}
	}

	return $res;
}

function authentification( string $login, string $pass ) {
	global $link, $db_table_users;
	$sql = "SELECT * FROM `{$db_table_users}` WHERE `login` LIKE '{$login}' AND `password` LIKE '{$pass}'";
	$res = $link->query( $sql );

	return $res->fetch_assoc();
}

function is_admin() {
	if ( $_COOKIE['auth'] ) {
		return true;
	}

	return false;
}

if ( isset( $_POST['name'] ) && isset( $_POST['task'] ) ) {
	$name = htmlspecialchars( strip_tags( $_POST['name'] ) );
	$task = htmlspecialchars( trim( $_POST['task'] ) );
	$sql = "INSERT INTO `{$db_table_tasks}` (`name`, `content`) VALUES ('{$name}', '{$task}');";
	$link->query( $sql );
	$link->close();
	header( "Location: /" );
	exit;
}
if ( isset( $_POST['task_id'] ) && isset( $_POST['task'] ) ) {
	update_task( $_POST['task_id'], $_POST['task'] );
}
if ( isset( $_POST['login'] ) && isset( $_POST['password'] ) ) {
	$login    = htmlspecialchars( strip_tags( $_POST['login'] ) );
	$password = htmlspecialchars( strip_tags( $_POST['password'] ) );
	if ( authentification( $login, $password ) ) {
		setcookie( 'auth', $login, time() + 3600 );
	}
}
if ( isset( $_GET['remove_task'] ) ) {
	remove_task( $_GET['remove_task'] );
	header( "Location: /" );
	exit;
}
if ( isset( $_GET['admin_exit'] ) ) {
	if ( $_GET['admin_exit'] == true ) {
		setcookie( 'auth', '', time() - 1 );
		header( 'Location: /' );
		exit;
	}
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
<header>
    <?php if( is_admin() ): ?>
        <div id="panel">
            <span>Hello&nbsp;</span><?= $_COOKIE['auth']; ?>
            <button class="js-admin-exit">Exit</button>
        </div>
    <?php else: ?>
        <div class="auth">
        <span>Authorization:</span>
        <form action="/" method="post">
            <input type="text" name="login" placeholder="Enter login" value="<?= htmlspecialchars(strip_tags($_POST['login'])); ?>" required>
            <input type="password" name="password" placeholder="Enter password">
            <button type="submit" class="btn">Login</button>
        </form>
    </div>
    <?php endif; ?>
</header>
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
                                <div class="btn-edit-task">
                                    <input type="hidden" name="task_id" value="<?= $_GET['edit_task']; ?>">
                                    <button type="submit" class="btn js-edit-ok">OK</button>
                                    <button class="btn js-edit-cancel">Cancel</button>
                                </div>
                            </form>
                        <?php else: ?>
                            <p><span>Task:</span> <?= $task['content']; ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="task-created">
                        <div class="task-created__bg">
	                        <?= date('H:i d.M.y', strtotime($task['date'])); ?>
                        </div>
                    </div>
                    <div class="answer-actions">
                        <?php if( is_admin() ): ?>
                            <button class="btn btn-edit js-task__edit">Edit</button>
                            <button class="btn btn-close js-task__remove">Х</button>
                        <?php endif; ?>
                    </div>
                </article>
		    <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<style>
    /* buttons style */
    .btn { border: 1px solid black; }
    .btn:hover { cursor: pointer; }
    .btn-close { background-color: brown }
    .btn-close:hover { background-color: darkred}
    .btn-edit { background-color: aquamarine }
    .btn-edit:hover { background-color: aqua }
    /* block answer with tasks */
    .task { border: 1px solid dimgrey; height: 150px; width: 300px; padding: 10px 7px; margin: 20px auto; position: relative; }
    .answer { display: flex; flex-flow: wrap; }
    .answer > article .answer-actions { position: absolute; top: 10px; right: 10px; }
    .answer > article .task-created { position: absolute; top: -10px; text-align: center; padding: 0; width: 100% }
    .answer > article .task-created__bg { background: white; width: 40%; margin: 0 auto; }
    /* authorization */
    .auth { width: 350px; padding: 7px; }
    .auth form { display: flex; flex-wrap: wrap; justify-content: flex-end; }
</style>
<script>
    let task = document.querySelectorAll('.answer > article');
    let adminPanel = document.getElementById('panel');
    // tasks
    for (let i = 0; i < task.length; i++) {
        let current = task[i];
        // remove task
        task[i].querySelector('.js-task__remove').onclick = () => {
            let isRemove = confirm('Are you sure to delete the task?');

            if (isRemove)
                document.location = "/?remove_task=" + current.id
        };
        // edit tast
        if( task[i].querySelector('.js-task__edit') ) {
            task[i].querySelector('.js-task__edit').onclick = () => {
                document.location = "/?edit_task=" + current.id
            };
        }
        // cancel edit task
        if( task[i].querySelector('.js-edit-cancel') ){
            task[i].querySelector('.js-edit-cancel').onclick = () => {
                document.location = "/"
            };
        }
    }
    // admin panel
    if( adminPanel ) {
        adminPanel.querySelector('.js-admin-exit').onclick = () => {
            document.location.href = '?admin_exit=true'
        }
    }
</script>
</body>
</html>