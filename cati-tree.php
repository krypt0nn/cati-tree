<?php

/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * @package     CATI Tree
 * @copyright   2021 Podvirnyy Nikita (Observer KRypt0n_)
 * @license     GNU GPL-3.0 <https://www.gnu.org/licenses/gpl-3.0.html>
 * @author      Podvirnyy Nikita (Observer KRypt0n_)
 * 
 * Contacts:
 *
 * Email: <suimin.tu.mu.ga.mi@gmail.com>
 * VK:    <https://vk.com/technomindlp>
 *        <https://vk.com/hphp_convertation>
 * 
 */

namespace CATI;

spl_autoload_register (function (string $class)
{
    if (file_exists ($file = __DIR__ .'/src/'. substr ($class, 5) .'.php'))
        require $file;
});
