<?php


namespace App\Model;


use Exception;

class Player
{
    const UP = 'up';
    const DOWN = 'down';
    const LEFT = 'left';
    const RIGHT = 'right';

    const DIRECTION = [self::UP, self::DOWN, self::LEFT, self::RIGHT];

    const PLAYER_TYPE_SEEK = 1;
    const PLAYER_TYPE_HIDE = 2;

    private $id;
    private $type = self::PLAYER_TYPE_SEEK;
    private $x;
    private $y;

    public function __construct($id, $x, $y)
    {
        $this->id = $id;
        $this->x = $x;
        $this->y = $y;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getX()
    {
        return $this->x;
    }

    public function getY()
    {
        return $this->y;
    }

    /**
     * @param $name
     * @param $arguments
     * @throws Exception
     */
    public function __call($name, $arguments)
    {
        switch ($name) {
            case "up" :
                $this->x--;
                break;
            case "down" :
                $this->x++;
                break;
            case "left" :
                $this->y--;
                break;
            case "right":
                $this->y++;
                break;
            default:
                throw new Exception("调用未定义方法");
        }
    }
}