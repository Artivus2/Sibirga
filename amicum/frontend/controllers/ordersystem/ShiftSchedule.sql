/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

ALTER TABLE `amicum3`.`brigade`
    CHANGE COLUMN `description` `description` VARCHAR(255) NOT NULL,
    DROP PRIMARY KEY,
    ADD PRIMARY KEY (`company_department_id`, `description`);
;
#25.08.2023