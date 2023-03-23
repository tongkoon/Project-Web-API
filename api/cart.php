<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// show foods
$app->get('/cart/{oid}', function (Request $request, Response $response, array $args) {
    $oid = $args['oid'];

    $conn = $GLOBALS["dbconn"];
    $sql = "select *,price*amount as price from cart,foods WHERE cart.fid = foods.fid and oid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i",$oid);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = array();
    while($row = $result->fetch_assoc()){
        array_push($data,$row);
    }
    $json = json_encode($data);
    $response->getBody()->write($json);
    return $response->withHeader('Content-type','application/json');
});

// select food items
$app->post('/cart/foodItem', function (Request $request, Response $response, array $args) {
    $body = $request->getBody();
    $bodyArr = json_decode($body,true);

    $conn = $GLOBALS["dbconn"];
    $sql = "select foods.name,amount,amount*foods.price as price
            from cart,foods
            WHERE cart.fid = foods.fid 
            and oid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i",$bodyArr['oid']);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $data = array();
    while($row = $result->fetch_assoc()){
        array_push($data,$row);
    }
    $json = json_encode($data);
    $response->getBody()->write($json);
    return $response->withHeader('content-type','application/json');
});

// insert
$app->post('/cart/insert', function (Request $request, Response $response, array $args) {
    $body = $request->getBody();
    $bodyArr = json_decode($body,true);
    foreach($bodyArr as $row){
        $json = json_encode($row);
        $bodyArr = json_decode($json,true);
        $oid = $bodyArr["oid"];
        $fid = $bodyArr["fid"];
        $amount = $bodyArr["amount"];
        // $response->getBody()->write(json_encode($fid." ".$amount));

        $conn = $GLOBALS["dbconn"];
        $sql = "insert into cart(oid,fid,amount) values(?,?,?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii",$oid,$fid,$amount);
        $stmt->execute();
    } 
    $value = array("status"=>'success');
    $json = json_encode($value);
    $response->getBody()->write($json);
    return $response->withHeader('content-type','application/json');
});

// calculate price
$app->post('/cart/calculatePrice', function (Request $request, Response $response, array $args) {
    $body = $request->getBody();
    $bodyArr = json_decode($body,true);
    $dataResult = array();
    foreach($bodyArr as $row){
        $json = json_encode($row);
        $bodyArr = json_decode($json,true);
        $fid = $bodyArr["fid"];
        $amount = $bodyArr["amount"];

        $conn = $GLOBALS["dbconn"];
        $sql = "select *,?*price as priceTotal
                from foods
                WHERE fid = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii",$amount,$fid);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        array_push($dataResult,$data);
    } 
    $response->getBody()->write(json_encode($dataResult));
    return $response->withHeader('content-type','application/json');
});

$app->post('/cart/amount', function (Request $request, Response $response, array $args) {
    // ส่ง oid fid amount มา
    $body = $request->getBody();
    $bodyArr = json_decode($body,true);

    $fid = $bodyArr["fid"];
    $oid = $bodyArr["oid"];
    $amount = $bodyArr["amount"];

    $conn = $GLOBALS["dbconn"];
    $sql = "select * from cart WHERE oid = ? and fid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii",$oid,$fid);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    if($data == null){
        $sql = "insert into cart (oid,fid,amount) values(?,?,?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii",$oid,$fid,$amount);
        $stmt->execute();
        $value = array("status"=>'success');
        $json = json_encode($value);
    }else{
        $amount = $data['amount'] + $amount;
        if($amount < 1){
            $amount = 1;
        }
        $sql = "update cart set amount = ? where oid = ? and fid = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii",$amount,$oid,$fid);
        $stmt->execute();
        $value = array("status"=>'success');
        $json = json_encode($value);
    }

    $response->getBody()->write($json);
    return $response->withHeader('content-type','application/json');
});

$app->post('/cart/delete', function (Request $request, Response $response, array $args) {
    // ส่ง oid fid มา
    $body = $request->getBody();
    $bodyArr = json_decode($body,true);

    $fid = $bodyArr["fid"];
    $oid = $bodyArr["oid"];

    $conn = $GLOBALS["dbconn"];
    $sql = "delete  from cart WHERE oid = ? and fid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii",$oid,$fid);
    $stmt->execute();
   
    $value = array("status"=>'success');
    $json = json_encode($value);
    $response->getBody()->write($json);
    return $response->withHeader('content-type','application/json');
});












// Price Total by oid -> total = ...
$app->get('/cart/priceTotal/{oid}', function (Request $request, Response $response, array $args) {
    $oid = $args["oid"];
    $conn = $GLOBALS["dbconn"];
    $sql = "select sum(amount*foods.price) as total
            from cart,foods
            WHERE cart.fid = foods.fid 
            and oid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i",$oid);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    $json = json_encode($data);
    $response->getBody()->write($json);
    return $response->withHeader('content-type','application/json');
});

// Price of Food by oid -> food name:... , price:...
$app->get('/cart/priceOfFood/{oid}', function (Request $request, Response $response, array $args) {
    $oid = $args["oid"];
    $conn = $GLOBALS["dbconn"];
    $sql = "select foods.name,amount*foods.price as price
            from cart,foods
            WHERE cart.fid = foods.fid 
            and oid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i",$oid);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $data = array();
    while($row = $result->fetch_assoc()){
        array_push($data,$row);
    }
    $json = json_encode($data);
    $response->getBody()->write($json);
    return $response->withHeader('content-type','application/json');
});

// Calculate Price of Food by fid,amount -> *,price of item
$app->get('/cart/calculatePrice/{fid}/{amount}', function (Request $request, Response $response, array $args) {
    $fid = $args["fid"];
    $amount = $args["amount"];
    $conn = $GLOBALS["dbconn"];
    $sql = "select *,?*price as price
            from foods
            WHERE fid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii",$amount,$fid);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $json = json_encode($data);
    $response->getBody()->write($json);
    return $response->withHeader('content-type','application/json');
});
?>