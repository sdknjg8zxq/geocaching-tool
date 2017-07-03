<?php

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
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

    return $app['twig']->render('cache.html.twig', array(
        'cache' => $cache
    ));

})->bind('cache');

// Create new cache(treasure)
$app->match('new', function (Request $request) use ($app) {

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
        ->add('clue', TextType::class, array(
            'constraints' => array(new Assert\Length(array('max' => 80))),
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

        // Save cache in database
        $user = $app['user'];
        $user->saveNewCache($data['author'], $data['title'], $data['message'], $data['clue'], $hash);

        // Save image
        $data['image']->move(getcwd() . '/files/images', $hash);

        return $app->redirect($app["url_generator"]->generate("homepage"));
    }

    return $app['twig']->render('new-cache.html.twig', array(
        'form' => $form->createView()
    ));

})->bind('new cache');

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
