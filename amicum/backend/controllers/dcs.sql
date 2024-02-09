/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

INSERT INTO `amicum3`.`Settings_DCS` (`id`, `title`)
VALUES ('10', 'ССД RTS');
INSERT INTO `amicum3`.`connect_string` (`title`, `ip`, `connect_string`, `Settings_DCS_id`, `source_type`)
VALUES ('RTS server', '127.0.0.1', '9991', '10', 'RTS');
UPDATE `amicum3`.`connect_string`
SET `title` = 'RTS server Сибиргинская',
    `ip`    = '192.168.4.27'
WHERE (`id` = '34489');

UPDATE `amicum3`.`connect_string`
SET `title`          = 'RTS server Сибиргинская',
    `ip`             = '192.168.4.25',
    `connect_string` = '5555'
WHERE (`id` = '34489');
INSERT INTO `amicum3`.`object` (`id`, `title`, `object_type_id`, `object_table`)
VALUES ('289', 'ССД RTS', '96', 'sensor');

#25.08.2023
