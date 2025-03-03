<?php
class User extends Query
{
    protected $table = 'users';
    protected $columns = ['id','name'];
    protected $primaryKeyColumn = 'id';
}
