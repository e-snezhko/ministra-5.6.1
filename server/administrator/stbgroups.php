<?php

\session_start();
\ob_start();
require __DIR__ . '/common.php';
use Ministra\Lib\Admin;
use Ministra\Lib\AdminAccess;
use Ministra\Lib\StbGroup;
$error = '';
$action_name = 'add';
$action_value = \_('Add');
\Ministra\Lib\Admin::checkAuth();
\Ministra\Lib\Admin::checkAccess(\Ministra\Lib\AdminAccess::ACCESS_VIEW);
foreach (@$_POST as $key => $value) {
    $_POST[$key] = \trim($value);
}
$stb_groups = new \Ministra\Lib\StbGroup();
if (@$_POST['add']) {
    \Ministra\Lib\Admin::checkAccess(\Ministra\Lib\AdminAccess::ACCESS_CREATE);
    $stb_groups->add($_POST['name']);
    \header('Location: stbgroups.php');
    exit;
}
$id = @(int) $_GET['id'];
$action = !empty($_POST['edit']) ? 'edit' : (!empty($_GET['del']) ? 'del' : \false);
if (!empty($id) && $action) {
    if ($action == 'edit' && @$_POST['name']) {
        \Ministra\Lib\Admin::checkAccess(\Ministra\Lib\AdminAccess::ACCESS_EDIT);
        $stb_groups->set(['name' => $_POST['name']], $_GET['id']);
    } elseif ($action == 'del') {
        \Ministra\Lib\Admin::checkAccess(\Ministra\Lib\AdminAccess::ACCESS_DELETE);
        $stb_groups->del($id);
    }
    \header('Location: stbgroups.php');
    exit;
}
if (@$_GET['edit'] && !empty($id)) {
    $action_name = 'edit';
    $action_value = \_('Save');
    $edit_group = $stb_groups->getById($id);
}
$groups = $stb_groups->getAll();
$debug = '<!--' . \ob_get_contents() . '-->';
\ob_clean();
echo $debug;
?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style type="text/css">

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-weight: bold;
        }

        td {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            text-decoration: none;
            color: #000000;
        }

        .list, .list td, .form {
            border-width: 1px;
            border-style: solid;
            border-color: #E5E5E5;
        }

        a {
            color: #0000FF;
            font-weight: bold;
            text-decoration: none;
        }

        a:link, a:visited {
            color: #5588FF;
            font-weight: bold;
        }

        a:hover {
            color: #0000FF;
            font-weight: bold;
            text-decoration: underline;
        }
    </style>
    <title><?php 
echo \_('Stb groups');
?></title>
</head>
<body>
<table align="center" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td align="center" valign="middle" width="100%" bgcolor="#88BBFF">
            <font size="5px" color="White"><b>&nbsp;<?php 
echo \_('Stb groups');
?>&nbsp;</b></font>
        </td>
    </tr>
    <tr>
        <td width="100%" align="left" valign="bottom">
            <a href="users.php"><< <?php 
echo \_('Back');
?></a>
        </td>
    </tr>
    <tr>
        <td align="center">
            <font color="Red">
                <strong>
                    <?php 
echo $error;
?>
                </strong>
            </font>
            <br>
            <br>
        </td>
    </tr>
    <tr>
        <td align="center">
            <table class='list' cellpadding='3' cellspacing='0'>
                <tr>
                    <td>ID</td>
                    <td><?php 
echo \_('Title');
?></td>
                    <td>&nbsp;</td>
                </tr>
                <?php 
foreach ($groups as $group) {
    echo '<tr>';
    echo '<td>' . $group['id'] . '</td>';
    echo '<td><a href="stbgroup_members.php?group_id=' . $group['id'] . '">' . $group['name'] . '</a></td>';
    echo '<td>';
    echo '<a href="?edit=1&id=' . $group['id'] . '">edit</a>&nbsp;';
    echo '<a href="?del=1&id=' . $group['id'] . '" onclick="if(confirm(\'' . \sprintf(\_('Are you sure you want to delete the group %s from the database?'), $group['name']) . '\')){return true}else{return false}">del</a>';
    echo '</td>';
    echo '</tr>';
}
?>
            </table>
        </td>
    </tr>
    <tr>
        <td align="center">
            <br>
            <br>
            <form method="POST">
                <table class="form">
                    <tr>
                        <td><?php 
echo \_('Name');
?></td>
                        <td><input type="text" name="name" value="<?php 
echo @$edit_group['name'];
?>"/></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td><input type="submit" name="<?php 
echo $action_name;
?>"
                                   value="<?php 
echo $action_value;
?>"/></td>
                    </tr>
                </table>
            </form>
        </td>
    </tr>
</table>

