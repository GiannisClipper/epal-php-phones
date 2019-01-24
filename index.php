<?php

//small php project, Create-Read-Update-Delete records (person name and phone number) in a MySQL database

  //function to check for empty or duplicated values (person or phone)
  function check_for_errors($db) { 
    $error=null;
    $id='';
    if (isset($_POST['id']))
      $id=$_POST['id'];
    $person=$_POST['person'];
    $phone=$_POST['phone'];
    if (!$person)
      $error='Empty person not allowed. ';
    else {
      $data=$db->query("SELECT * FROM tb_phones WHERE `person`='$person' AND NOT `id`='$id';");
      if ($data->fetch_assoc())
        $error=$error."Duplicate person ($person) not allowed. ";
    }
    if (!$phone)
      $error=$error.'Empty phone not allowed. ';
    else {
      $data=$db->query("SELECT * FROM tb_phones WHERE `phone`='$phone' AND NOT `id`='$id';");
      if ($data->fetch_assoc())
        $error=$error."Duplicate phone ($phone) not allowed. ";
    }
    return $error;
  }

  //--------------------------------------------------------------------------

  //code execution starts here
  $db_error=null;
  $user_error=null;
 
  //connect to database
  $config=file_get_contents('./config.json');
  $config=json_decode($config);
  try {
    //$db=new mysqli("localhost", "root", "", "db_phones"); 
    $db=new mysqli($config->host, $config->user, $config->password, $config->database); 

    if ($db->connect_errno)
      $db_error="DB connection error (".$db->connect_errno.") ".$db->connect_error;  
  } catch (mysqli_sql_exception $e) {
    $db_error=$e->getMessage();
  }
  
  if (!$db_error) { 
    $date=getdate();
    $timestamp=mktime($date['hours'],$date['minutes'],$date['seconds'],$date['mon'],$date['mday'],$date['year']);
    $timelimit=$timestamp-3600; //60 minumtes X 60 seconds=3600

    //delete records 1-hour older 
    $db->query("DELETE FROM `tb_phones` WHERE `timestamp`<'$timelimit';");
    $db->commit();

    //add a new record
    if (isset($_POST['add'])) {
      $person=$_POST['person'];
      $phone=$_POST['phone'];
      $user_error=check_for_errors($db);
      if (!$user_error) {
        $db->query("INSERT INTO `tb_phones` (`person`, `phone`, `timestamp`) VALUES ('$person', '$phone', '$timestamp');");
        $db->commit();
      }
 
    //modify a record
    } elseif (isset($_POST['save'])) {
      $id=$_POST['id'];
      $person=$_POST['person'];
      $phone=$_POST['phone'];
      $user_error=check_for_errors($db);
      if (!$user_error) {
        $db->query("UPDATE `tb_phones` SET `person`='$person', `phone`='$phone', `timestamp`='$timestamp' WHERE `id`='$id';");
        $db->commit();
      }

    //delete a record
    } elseif (isset($_POST['delete'])) {
      $id=$_POST['id'];
      $db->query("DELETE FROM `tb_phones` WHERE `id`='$id';");
      $db->commit();
    }

    //get all records
    $data=$db->query("SELECT * FROM tb_phones;");
  }
?>

<DOCTYPE html>
<html>
<head>
  <title>Phones App</title>
  <link href="style.css" rel="stylesheet">
</head>

<body>
  <div class="container">
    <h1>Phones App</h1>
    <?php
      //display error messages (if any)
      $error=$db_error?$db_error:($user_error?$user_error:'');
      echo "<p class='error'>$error</p>";

      if (!$db_error) {
        //display responded records & elements to modify/delete any of them
        while ($row=$data->fetch_assoc()) {
          $id=$row['id'];
          $person=$row['person'];
          $phone=$row['phone'];
          echo "
            <form action='index.php' method='POST'>
              <span class='id'><input type='hidden' name='id' value='$id'></span>
              <span class='person'><input type='text' name='person' value='$person'></span>
              <span class='phone'><input type='tel' name='phone' value='$phone'></span>
              <span class='save'><input type='submit' name='save' value='Save'></span>
              <span class='delete'><input type='submit' name='delete' value='Delete'></span>
            </form>
          ";
        }

        //display elements to create a new record or refresh page
        echo "
          <form action='index.php' method='POST'>
            <span class='person'><input type='text' name='person' placeholder='Person'></span>
            <span class='phone'><input type='tel' name='phone' placeholder='Phone'></span>
            <span class='save'><input type='submit' name='add' value='Add'></span>
            <span class='refresh'><input type='submit' name='refresh' value='Refresh'></span>
          </form>
        ";      
      }

      echo "<footer>Athens 2019, Giannis Clipper</footer>";
?>
  </div>
  <div class="under container">
    <span>It's a demo, each record automatically deleted one hour after created or last updated.</span>
  </div>
</body>
</html>