<?php
$users = new Query();
$data = $users->table('users')->with(['belongsTo.many.roles:*:user_role'])->get();

printAsJson($data, JSON_PRETTY_PRINT);
