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
$postsOnPage = 3;

function get_tasks( bool $pagination = false ) {
	global $link, $db_base, $db_table_tasks, $postsOnPage;
	if ( $pagination === false ) {
		$startPost = (int) ( isset( $_GET['page'] ) && $_GET['page'] > 0 ) ? $_GET['page'] * $postsOnPage - $postsOnPage : 0;
		$sort = ( isset( $_GET['sort'] ) ) ? " ORDER BY `{$_GET['sort']}` " : ' ';
        $sql = "SELECT * FROM {$db_base}.{$db_table_tasks}{$sort}LIMIT {$startPost}, {$postsOnPage}";
    } else {
		$sql = "SELECT * FROM {$db_base}.{$db_table_tasks}";
	}
	$res = [];
	if ( $result = $link->query( $sql ) ) {
		while ( $row = $result->fetch_assoc() ) {
			array_push( $res, $row );
		}
	}

    return $res;
}

function add_task($_post) {
    global $db_table_tasks, $link;
	$name = htmlspecialchars( strip_tags( $_post['name'] ) );
	$task = addslashes( htmlspecialchars( trim( $_post['task'] ) ) );
	$email = $_post['email'];
	$sql = "INSERT INTO `{$db_table_tasks}` (`name`, `content`, `email`) VALUES ('{$name}', '{$task}', '{$email}');";
	$link->query( $sql );
	$link->close();
}

function remove_task( int $id ) {
	global $link, $db_table_tasks;
	$sql = "DELETE FROM `{$db_table_tasks}` WHERE `{$db_table_tasks}`.`id` = {$id}";
	$link->query( $sql );
	$link->close();
}

function update_task($_post) {
	global $link, $db_table_tasks;
	$id      = (int) $_post['task_id'];
	$status  = (bool) ( isset($_post['status']) ) ? 1 : 0;
	$name    = (string) $_post['name'];
	$email   = (string) $_post['email'];
	$content = (string) addslashes(htmlspecialchars($_post['task']));
	$sql     = "UPDATE `{$db_table_tasks}` 
                SET `content` = '{$content}',
                    `status`  = {$status},
                    `name`    = '{$name}',
                    `email`   = '{$email}'
                WHERE `{$db_table_tasks}`.`id` = {$id};";
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

function get_pagination( int $countPosts = 1 ) {
	if ( $countPosts === 1 ) {
		return;
	}
	global $postsOnPage;
	$pageCurrent = empty( $_GET['page'] ) ? 1 : intval( $_GET['page'] );
	$pageLast    = (int) ceil( $countPosts / $postsOnPage );
	$res = [];
	if($countPosts <= $postsOnPage){
	    return false;
    }
	for ( $i = (int) 1; $i <= $pageLast; $i ++ ) {
	    // prev page
		if ( $i === 1 && $pageCurrent !== 1 ) {
			$_GET['page'] = $pageCurrent - 1;
			$link = http_build_query($_GET);
			array_push($res, "<a href=\"?{$link}\"> < </a>");
		}
		// current page
		if ( $pageCurrent === $i ) {
			array_push($res, "<span class=\"active\">{$i}</span>");
		} else {
		    $_GET['page'] = $i;
		    $link = http_build_query($_GET);
			array_push($res, "<a href=\"?{$link}\"> {$i} </a>");
		}
		// next page
		if ( $i === $pageLast && $pageCurrent !== $pageLast ) {
			$_GET['page'] = $pageCurrent + 1;
			$link = http_build_query($_GET);
			array_push($res, "<a href=\"?{$link}\"> > </a>");
		}
	}

	return implode($res);
}

function get_sorting() {
	$res   = [];
	$sort  = [
		'name'   => 'Sort by name',
		'email'  => 'Sort by email',
		'status' => 'Sort by status',
	];
	$_get = $_GET;

	$res[] = '<select name="sort" onchange="if (this.value) window.location.href = this.value">>';
	foreach ( $sort as $key => $item ) {
		$selected = '';
		if( isset($_GET['sort']) && ! empty( $_GET['sort'] && $_GET['sort'] === $key ) ){
			$selected = ' selected';
		}
		$_get['sort'] = $key;
		$link = http_build_query($_get);
        array_push( $res, "<option{$selected} value=\"?{$link}\">{$item}</option>" );
	}
	$res[] = '</select>';

	return implode( $res );
}

if ( isset( $_POST['add_task'] ) ) {
	add_task($_POST);
	header( "Location: /" );
	exit;
}
if ( isset( $_POST['edit_ok'] ) ) {
	update_task($_POST);
}
if ( isset( $_POST['auth'] ) ) {
	$login    = htmlspecialchars( strip_tags( $_POST['login'] ) );
	$password = htmlspecialchars( strip_tags( $_POST['password'] ) );
	if ( authentification( $login, $password ) ) {
		setcookie( 'auth', $login, time() + 3600 );
	}
	header( 'Location: /' );
	exit;
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
            <input type="text" name="login" placeholder="Enter login" value="<?= htmlspecialchars(strip_tags($_POST['login'])); ?>" autocomplete="off" required>
            <input type="password" name="password" placeholder="Enter password">
            <button type="submit" class="btn">Login</button>
            <input type="hidden" name="auth" value="1">
        </form>
    </div>
    <?php endif; ?>
</header>
<main>
    <div class="task-add">
        <form action="/" method="POST">
            <p><input type="text" name="name" placeholder="Enter name" minlength=3 required></p>
            <p><input type="email" name="email" placeholder="Enter email"></p>
            <p><textarea name="task" cols="30" rows="10" placeholder="Enter task" minlength=15 required></textarea></p>
            <p><input type="submit" value="Add"></p>
            <input type="hidden" name="add_task" value="1">
        </form>
    </div>
    <?php if ( $tasks = get_tasks(true) ): ?>
        <div class="sorting"><?= get_sorting(); ?></div>
        <div class="answer">
		    <?php foreach ( get_tasks() as $task ): ?>
                <article class="task<?= $task['status'] ? ' complete' : '' ?>" data-status="<?= $task['status']; ?>" id="<?= $task['id']; ?>">
	                <?php if( isset($_GET['edit_task']) && $_GET['edit_task'] === $task['id'] ): ?>
                        <div class="task-content">
                            <form action="/" method="post">
                                <label for="edit-task__name">Name:</label>
                                <input id="edit-task__name" type="text" name="name" value="<?= $task['name']; ?>" minlength=3 required>
                                <br>
                                <label for="edit-task__email">eMail:</label>
                                <input id="edit-task__email" type="email" name="email" value="<?= $task['email']; ?>">
                                <p>
                                    <label for="edit-task__content">Task:</label>
                                    <textarea id="edit-task__content" name="task" class="js-edit-content" cols="40" rows="10"><?= $task['content']; ?></textarea>
                                </p>
                                <div class="btn-edit-task">
                                    <input type="hidden" name="task_id" value="<?= $_GET['edit_task']; ?>">
                                    <input type="hidden" name="edit_ok" value="1">
                                    <button type="submit" class="btn js-edit-ok">OK</button>
                                    <button class="btn js-edit-cancel">Cancel</button>
                                    <div class="edit-task__status-wrap">
                                        <label for="edit-task__status">Status task</label>
                                        <input id="edit-task__status" type="checkbox" name="status"<?= $task['status'] == 1 ? ' checked' : ''; ?>>
                                    </div>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <div><b>Name:&nbsp;</b><span><?= $task['name']; ?></span></div>
                        <div><b>eMail:&nbsp;</b><span><?= $task['email']; ?></span></div>
                        <div class="task-content">
                            <p>
                                <span><b>Task:</b>&nbsp;</span><?= $task['content']; ?>
                            </p>
                        </div>
	                <?php endif; ?>
                    <div class="task-created">
                        <div class="task-created__bg">
	                        <?= date('H:i d.M.y', strtotime($task['date'])); ?>
                        </div>
                    </div>
	                <?php if( is_admin() ): ?>
                        <div class="answer-actions">
                            <button class="btn btn-edit js-task__edit">Edit</button>
                            <button class="btn btn-close js-task__remove">Х</button>
                        </div>
	                <?php endif; ?>
                </article>
		    <?php endforeach; ?>
        </div>
        <div class="nav nav-page"><?= get_pagination(count($tasks)); ?></div>
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
    .btn-edit-task { position: absolute; display: flex; bottom: 10px; flex-direction: column; }
    .btn-edit-task label { background-color: white; padding: 0 5px }
    /* block answer with tasks */
    .task { border: 2px solid dimgrey; height: 300px; width: 300px; padding: 10px 7px; margin: 20px auto; position: relative; }
    .complete { border: 2px solid green; background-color: lightgreen; }
    .task-content { height: 90%; overflow: auto; overflow-x: hidden; }
    .task-content p { margin-bottom: 10px; }
    .edit-task__status-wrap { background: white; margin: 0 auto; border-radius: 7px; width: 100%; text-transform: uppercase; }
    .answer { display: flex; flex-flow: wrap; }
    .answer > article .answer-actions { position: absolute; top: 10px; right: 10px; }
    .answer > article .task-created { position: absolute; top: -10px; text-align: center; padding: 0; width: 100% }
    .answer > article .task-created__bg { background: white; width: 40%; margin: 0 auto; border-radius: 7px; }
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
        if( task[i].querySelector('.js-task__remove') ) {
            task[i].querySelector('.js-task__remove').onclick = () => {
                let isRemove = confirm('Are you sure to delete the task?');

                if (isRemove && document.location.search.length)
                    document.location = "/" + document.location.search + "&remove_task=" + current.id
                else if( isRemove )
                    document.location = "/" + "?remove_task=" + current.id
            };
        }
        // edit tast
        if( task[i].querySelector('.js-task__edit') ) {
            task[i].querySelector('.js-task__edit').onclick = () => {
                if( document.location.search.length ) {
                    document.location = "/" + document.location.search + "&edit_task=" + current.id
                } else {
                    document.location = "/" + "?edit_task=" + current.id
                }
            };
        }
        // cancel edit task
        if( task[i].querySelector('.js-edit-cancel') ){
            task[i].querySelector('.js-edit-cancel').onclick = () => {
                document.location = "/" + document.location.search
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