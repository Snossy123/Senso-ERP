-- Never commit non-default credentials in git-tracked SQL.
-- Keep a neutral local-dev password here and rotate via runtime secrets outside VCS.
ALTER USER 'admin'@'%' IDENTIFIED WITH mysql_native_password BY 'admin';
ALTER USER 'admin'@'localhost' IDENTIFIED WITH mysql_native_password BY 'admin';
ALTER USER 'root'@'%' IDENTIFIED WITH mysql_native_password BY 'root';
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'root';
FLUSH PRIVILEGES;
