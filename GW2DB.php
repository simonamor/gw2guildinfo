<?php

class GW2DB {
    private $conn = null;

    function connect() {

        // Return the connection object if we're already connected.
        if ($this->conn) {
            return $this->conn;
        }

        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOSTNAME . ";dbname=" . DB_DATABASE,
                DB_USERNAME, DB_PASSWORD
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // echo "Connected successfully";
        }
        catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
        return $this->conn;
    }

    /**
     * @method get_account( array( account_id => 3 ) );
     * @method get_account( array( name => 'Leaky.1472' ) );
     */
    function get_account($params) {
        if (array_key_exists('account_id', $params)) {
            // select by account_id
            $stmt = $this->conn->prepare("SELECT * FROM account WHERE account_id=?");
            $stmt->bindValue(1, $params['account_id']);
            $stmt->execute();
            $account = $stmt->fetch();

            return $account;
        } else if (array_key_exists('name', $params)) {
            // select by account name
            $stmt = $this->conn->prepare("SELECT * FROM account WHERE name=?");
            $stmt->bindValue(1, $params['name']);
            $stmt->execute();
            $account = $stmt->fetch();

            return $account;
        }
        return null;
    }

    /**
     * @method void log(integer $account_id, string $action, string $message)
     */
    function log($account_id, $action, $message) {
        $stmt = $this->conn->prepare("INSERT INTO log VALUES(0,?,?,NOW(),?)");
        $stmt->bindValue(1, $account_id);
        $stmt->bindValue(2, $action);
        $stmt->bindValue(3, $message);
        $stmt->execute();
    }
}

