TRUNCATE TABLE `mc_sendinblue_list`;
DROP TABLE `mc_sendinblue_list`;
TRUNCATE TABLE `mc_sendinblue`;
DROP TABLE `mc_sendinblue`;

DELETE FROM `mc_admin_access` WHERE `id_module` IN (
    SELECT `id_module` FROM `mc_module` as m WHERE m.name = 'sendinblue'
);