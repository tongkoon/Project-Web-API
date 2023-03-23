<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request; 

$app->post('/customer', function (Request $request, Response $response, array $args) {
    $body = $request->getBody();
    $bodyArr = json_decode($body,true);

    $conn = $GLOBALS["dbconn"];
    $sql = "select * from customer where cid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i",$bodyArr["cid"]);
    $stmt->execute();
    $result = ($stmt->get_result())->fetch_assoc();
    $json = json_encode($result);
    $response->getBody()->write($json);
    return $response->withHeader('content-type','application/json');
});

// update
$app->post('/customer/update', function (Request $request, Response $response, array $args) {
    $body = $request->getBody();
    $bodyArr = json_decode($body,true);

    $conn = $GLOBALS["dbconn"];
    $sql = "update customer set name = ?,phone = ?,address = ? where cid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi",$bodyArr["name"],$bodyArr["phone"],$bodyArr["address"],$bodyArr["cid"]);
    $stmt->execute();
    $value = array("status"=>'success');
    $json = json_encode($value);
    $response->getBody()->write($json);
    return $response->withHeader('content-type','application/json');
});

$app->post('/customer/register', function (Request $request, Response $response, array $args) {
    $body = $request->getBody();
    $bodyArr = json_decode($body,true);
    $hash = password_hash($bodyArr["password"],PASSWORD_DEFAULT);

    $conn = $GLOBALS["dbconn"];
    $sql = "select * from customer where username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$bodyArr["username"]);
    $stmt->execute();
    $result = $stmt->get_result();
 
    if($result->num_rows===0){
        $sql = "insert into customer (username,password) values(?,?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss",$bodyArr["username"],$hash);
        $stmt->execute();
        $last_id = $conn->insert_id;
        $value = array("status"=>'register',"cid"=>$last_id);
        $json = json_encode($value);
        $response->getBody()->write($json);
    }
    else{
        $value = array("status"=>'fail');
        $json = json_encode($value);
        $response->getBody()->write($json);
    }
    return $response->withHeader('content-type','application/json');
});

$app->post('/customer/login', function (Request $request, Response $response, array $args) {
    $body = $request->getBody();
    $bodyArr = json_decode($body,true);

    $conn = $GLOBALS["dbconn"];
    $sql = "select * from customer where username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$bodyArr["username"]);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows===1){
        $row = $result->fetch_assoc();
        $pwdInDB = $row["password"];
        if(password_verify($bodyArr["password"],$pwdInDB)){
            $cid = $row['cid'];
            $status = 2;
            $sql = "select * from iorder where cusID = ? and status = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii",$cid,$status);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();

            $value = array("status"=>'login',"cid"=>$row["cid"],"username"=>$row["username"],"oid"=>$data["oid"]);
            $json = json_encode($value);
            $response->getBody()->write($json);
        }else{
            $value = array("status"=>'fail');
            $json = json_encode($value);
            $response->getBody()->write($json);
        }
    }
    else{
        $value = array("status"=>'notFound');
        $json = json_encode($value);
        $response->getBody()->write($json);
    }
    return $response->withHeader('content-type','application/json');
});

?>