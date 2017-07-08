<?php

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use GeocachingTool\User;
use GeocachingTool\Cache;
use Symfony\Component\Validator\Constraints as Assert;

//Request::setTrustedProxies(array('127.0.0.1'));

// Homepage
$app->get('/', function () use ($app) {
    $user = $app['user'];
    $caches = $user->getCaches();
    return $app['twig']->render('index.html.twig', array(
        'caches' => $caches
    ));
})->bind('homepage');

// View cache(treasure)
$app->get('/cache/{hash}', function (Silex\Application $app, $hash) {
    // Get cache by hash
    $cache = Cache::getCacheByHash($app, $hash);

    // Return message if cache was not found
    if (!$cache) {
        $app->error(function (\Exception $e, $code) {
            return new Response('Treasure not found, sorry.');
        });
    }

    // Increase view count if it's not a preview
    $isPreview = isset($_REQUEST['preview']);
    if (!$isPreview) {
        Cache::incrementView($app, $cache['hash']);
    }

    return $app['twig']->render('cache.html.twig', array(
        'cache' => $cache
    ));

})->bind('cache');

// Create new cache(treasure)
$app->match('/new', function (Request $request) use ($app) {
    $form = $app['form.factory']->createBuilder(FormType::class)
        ->add('title', TextType::class, array(
            'constraints' => array(new Assert\NotBlank(), new Assert\Length(array('max' => 80))),
            'label' => 'Title *'
        ))
        ->add('author', TextType::class, array(
            'constraints' => array(new Assert\Length(array('max' => 50))),
            'required' => false
        ))
        ->add('message', TextareaType::class, array(
            'constraints' => array(new Assert\NotBlank(), new Assert\Length(array('max' => 140))),
            'label' => 'Message *'
        ))
        ->add('clue', TextareaType::class, array(
            'constraints' => array(new Assert\Length(array('max' => 140))),
            'required' => false
        ))
        ->add('image', FileType::class, array(
            'constraints' => array(new Assert\Image(array('mimeTypes' => 'image/*', 'maxSize' => '2M'))),
            'label' => 'Image *'
        ))
        ->getForm();

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $data = $form->getData();

        // ... perform some action, such as saving the data to the database
        $hash = Cache::getNewHash();

        $cacheUrl = 'https://' . $request->getHost() . '/cache/' . $hash;

        // Get short URL from Google
        $client = new Google_Client();
        $client->addScope("https://www.googleapis.com/auth/urlshortener");
        $client->setDeveloperKey($app['google.options']['api_key']);

        $service = new Google_Service_Urlshortener($client);
        $url = new Google_Service_Urlshortener_Url();
        $url->longUrl = $cacheUrl;
        $short = $service->url->insert($url);
        $shortURL = $short->getId();

        // Save cache in database
        $user = $app['user'];
        $user->saveNewCache($data['author'], $data['title'], $data['message'], $data['clue'], $hash, $shortURL);

        // Save image
        $data['image']->move(getcwd() . '/files/images', $hash);

        return $app->redirect($app["url_generator"]->generate("homepage"));
    }

    return $app['twig']->render('new-cache.html.twig', array(
        'form' => $form->createView()
    ));

})->bind('new cache');

// Delete a cache
$app->match('/delete/{hash}', function (Request $request, $hash) use ($app) {

    // Check if cache exists and that it belongs to this users
    $user = $app['user'];

    // If cache is not found for this user, redirect to homepage
    $cache = $user->getCacheByHash($hash);
    if (!$cache) {
        return $app->redirect($app["url_generator"]->generate("homepage"));
    }

    // Set default values for form
    $defaults =  array(
        'hash' => $hash
    );

    // Build form
    $form = $app['form.factory']->createBuilder(FormType::class, $defaults)
        ->add('hash', HiddenType::class, array(
            'constraints' => array(new Assert\NotBlank())
        ))
        ->getForm();

    // Handle form submission
    $form->handleRequest($request);

    // If form is submitted and valid
    if ($form->isSubmitted() && $form->isValid()) {
        $data = $form->getData();

        // If cache is not found for this user, redirect to homepage
        $cache = $user->getCacheByHash($hash);
        if (!$cache) {
            return $app->redirect($app["url_generator"]->generate("homepage"));
        }

        // Delete cache
        $user->deleteCache($data['hash']);
        unlink(getcwd() . '/files/images/' . $hash); // Delete image
        return $app->redirect($app["url_generator"]->generate("homepage"));
    }

    // Normal view rendering
    return $app['twig']->render('delete-cache.html.twig', array(
        'form' => $form->createView(),
        'cache' => $cache
    ));

})->bind('delete cache');

// Help
$app->get('/help', function  () use ($app) {

    return $app['twig']->render('help.html.twig', array(
        'version' => $app['version'],
        'github_url' => $app['github_url']
    ));
})->bind('help');

// Error
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

// Always use HTTPS
$app['require_https'] ? $app['controllers']->requireHttps() : false;
