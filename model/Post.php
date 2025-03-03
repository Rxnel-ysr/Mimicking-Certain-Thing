<?php
class Post extends Query
{
    protected $table = 'posts';
    protected $columns = ['id', 'user_id', 'title', 'content'];
    protected $primaryKeyColumn = 'id';
}
