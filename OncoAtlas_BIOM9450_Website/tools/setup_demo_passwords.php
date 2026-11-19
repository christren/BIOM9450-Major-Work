<?php
declare(strict_types=1);
// CLI only: change the three existing sample accounts to fresh random passwords.
// This does not insert extra records or change any TCGA data.
if(PHP_SAPI!=='cli'){http_response_code(403);exit('Run from the terminal only.');}
require_once __DIR__.'/../app/bootstrap.php';
$emails=['oncologist@example.test','researcher@example.test','patient@example.test'];
try{
    $pdo=db();$pdo->beginTransaction();$results=[];
    foreach($emails as $email){
        $password=bin2hex(random_bytes(10));$hash=password_hash($password,PASSWORD_BCRYPT);
        $stmt=$pdo->prepare('UPDATE AppUser SET Password=? WHERE Email=?');$stmt->execute([$hash,$email]);
        if($stmt->rowCount()!==1)throw new RuntimeException("Missing sample account: $email. Check the database import.");
        $results[$email]=$password;
    }
    $pdo->commit();
    echo "Passwords set for sample accounts. Keep these credentials private.\n\n";
    foreach($results as $email=>$password)echo "$email  ->  $password\n";
    echo "\nSave these passwords securely; they are not stored in plaintext.\n";
}catch(Throwable $e){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();fwrite(STDERR,'Setup failed: '.$e->getMessage()."\n");exit(1);}
