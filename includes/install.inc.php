<?php include "header.inc.php"; ?>
<div class="container mb-5">
  <div class="jumbotron">
    <h1 class="display-3">Save Game Pro Installation</h1>
    <p class="lead">This is Save Game Pro Cloud installation, follow the steps below to set up the Save Game Pro Cloud and use the API in your game to save players data to cloud.</p>
    <hr class="my-4">
    <p>Learn more about the Save Game Pro Cloud installation.</p>
    <p class="lead">
      <a class="btn btn-primary btn-lg" href="#" role="button">Learn more</a>
    </p>
  </div>
  <form method="POST" action="install.php">
    <p>Fill the fields below to setup the server and database.</p>
    <div class="form-group">
      <label for="db_host">Database Host</label>
      <input type="text" class="form-control" id="db_host" name="db_host" placeholder="e.g. localhost" value="localhost" required>
    </div>
    <div class="form-group">
      <label for="db_name">Database Name</label>
      <input type="text" class="form-control" id="db_name" name="db_name" placeholder="e.g. savegamepro" value="savegamepro" required>
    </div>
    <div class="form-group">
      <label for="db_user">Database User</label>
      <input type="text" class="form-control" id="db_user" name="db_user" placeholder="e.g. root" required>
    </div>
    <div class="form-group">
      <label for="db_pass">Database Password</label>
      <input type="password" class="form-control" id="db_pass" name="db_pass" placeholder="Password">
    </div>
    <div class="form-group">
      <label for="upload_folder">Upload Folder</label>
      <input type="text" class="form-control" id="upload_folder" name="upload_folder" placeholder="e.g. <?php echo dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR; ?>" value="<?php echo dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR; ?>">
      <small id="upload_folder_help" class="form-text text-muted">We need the absolute path to the folder, for example <?php echo dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR; ?>, and the path should end with a Slash, otherwise it won't work, current OS slash is <?php echo DIRECTORY_SEPARATOR; ?></small>
    </div>
    <button type="submit" class="btn btn-primary">Submit</button>
  </form>
</div>
<?php include "footer.inc.php"; ?>
