<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraints as Assert;

//Request::setTrustedProxies(array('127.0.0.1'));

$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html.twig', array());
})
->bind('homepage');

$app->get('api/vehicles', function() use($app) {
    // fetch all record and return - todo:paginate,sort,filter,etc
    $vehicles = $app['db']->fetchAll('SELECT * FROM vehicles');
    return $app->json($vehicles, Response::HTTP_OK); 
});

$app->post('api/vehicles', function(Request $request) use($app) {

    // get the post data
    $vehicleData = [
        "name" => $request->get('name'),
        "price" => (int)$request->get('price'),
        "location" => $request->get('location'),
        // "displacement" => $request->get('displacement'),
        // "power" => $request->get('power'),
    ];

    // create validation rules
    $vehicleConstraint = new Assert\Collection([
        "name" => new Assert\NotBlank(),
        "location" => new Assert\NotBlank(),
        "price" => [
            new Assert\NotBlank(), 
            new Assert\Type([
                'type'    => 'integer',
                'message' => 'The value {{ value }} is not a valid {{ type }}.',
            ])
        ],
        // "power" => new Assert\NotBlank(),
        // "displacement" => new Assert\NotBlank(),
    ]);

    // check if post data are valid
    $validationErrors = $app['validator']->validate($vehicleData, $vehicleConstraint); 

    // if not valid, return the errors
    if(count($validationErrors) > 0)
    {
        $responseError = []; 
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        foreach ($validationErrors as $error) { 
            // need to use this to remove "[]" around "getPropertyPath()"
            $propertyAccessor->setValue($responseError, $error->getPropertyPath(), $error->getMessage()); 
        }

        // return failed response
        return $app->json(
            [
                "success"=>false, 
                "message"=>"All fields are required",
                "errors"=>$responseError
            ], 
            Response::HTTP_UNPROCESSABLE_ENTITY
        );
    } 

    // complete the vehicle data for saving to DB - workaround to avoid "unexpected field" issue when validating
    $vehicleData['displacement'] = $request->get('displacement');
    $vehicleData['power'] = $request->get('power');

    // if valid, save data to database
    $addToVehicle = $app['db']->insert('vehicles', $vehicleData);
    
    // check if saving is success
    if(!$addToVehicle)
    {
        // if not success, return failed response
        return $app->json(
            [
                "success"=>false, 
                "message"=>"Unable to add vehicle to list"
            ], 
            Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }

    // return success result if validated and saved data to DB
    return $app->json(
        [
            "success"=>true, 
            "message"=>"Vehicle was added"
        ], 
        Response::HTTP_OK
    ); 
});

$app->get('api/displacements', function() use($app) {
    // fetch unique displacement values
    $displacements = $app['db']->fetchAll('SELECT DISTINCT displacement FROM vehicles WHERE displacement IS NOT NULL');
    return $app->json($displacements, Response::HTTP_OK); 
});

$app->error(function (\Exception $e, Request $request, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    // 404.html, or 40x.html, or 4xx.html, or error.html
    $templates = array(
        'errors/'.$code.'.html.twig',
        'errors/'.substr($code, 0, 2).'x.html.twig',
        'errors/'.substr($code, 0, 1).'xx.html.twig',
        'errors/default.html.twig',
    );

    return new Response($app['twig']->resolveTemplate($templates)->render(array('code' => $code)), $code);
});
