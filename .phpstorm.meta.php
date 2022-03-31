<?php

namespace PHPSTORM_META {
    override(\Psr\Container\ContainerInterface::get(0), map([
        '' => '@',
    ]));

    override(\Illuminate\Contracts\Container\Container::get(0), map([
        '' => '@',
    ]));
}
