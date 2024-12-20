<?php
require_once __DIR__ . '/../models/Recipe.php';
require_once __DIR__ . '/../models/Ingredient.php';
require_once __DIR__ . '/../models/Comment.php';


require_once __DIR__ . '/../helpers/Validator.php';
require_once __DIR__ . '/../helpers/ImgConverter.php';
require_once __DIR__ . '/../config/config.php';


class RecipeController
{
    private $recetaModel;
    private $ingredientModel;
    private $commentModel;
    private $validator;
    private $converter;

    public function __construct()
    {
        $this->recetaModel = new Recipe(); // Instancia del modelo
        $this->ingredientModel = new Ingredient();
        $this->commentModel = new Comment();
        $this->validator = new Validator(); // Instancia del helper
        $this->converter = new ImgConverter(80);
    }

    public function guardarReceta($data, $file)
    {
        $datosCombinados = array_merge($_POST, $_FILES);

        session_start();
        try {

            $rules = [
                'titulo' => [
                    'required' => true,
                    'string' => true,
                    'max' => 200
                ],
                'descripcion' => [
                    'required' => true,
                    'string' => true,
                    'max' => 2000
                ],
                'pasos' => [
                    'required' => true,
                    'string' => true,
                    'max' => 2000
                ],
                'tiempo' => [
                    'required' => true,
                    'string' => true,
                    'max' => 50
                ],
                'ingrediente' => [
                    'required' => true,
                    'array' => true,
                    'minItems' => 1
                ],
                'imagen' => [
                    'required' => false,
                    'image' => true
                ],
                'tipo_comida' => [
                    'required' => true,
                    'enum' => ['Desayunos', 'Almuerzos', 'Cenas']
                ]
            ];


            if ($this->validator->validate($datosCombinados, $rules)) {

                $titulo = $data['titulo'];
                $descripcion = $data['descripcion'];
                $pasos = $data['pasos'];
                $tiempo = $data['tiempo'];
                $ingredientes = $data['ingrediente'];
                $imagen = $file['imagen'];
                $tipo_comida = $data['tipo_comida'];



                //validaciones
                if (!isset($_SESSION['user'])) {
                    return header('location: ' . BASE_URL . '/views/auth/login.php');
                }

                // Subir imagen (si existe)
                $uniqueName = null;
                if (!empty($imagen['tmp_name'])) {
                    try {
                            // Crear una instancia de la clase con calidad personalizada (80%)
                            $converter = new ImgConverter(80);

                            // Ruta de entrada y salida
                            $uploadedFile = $_FILES['imagen']['tmp_name'];


                            // Convertir la imagen
                            $webpData = $converter->convertToWebp($uploadedFile);

                            if($webpData) {
                                $uniqueName = $this->converter->uploadImage($imagen, $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/public/img/');
                            }
                    } catch (Exception $e) {
                            // Manejar errores y redirigir con un mensaje
                            $_SESSION['mensaje'] = $e->getMessage();
                            header("Location: " . BASE_URL . "/views/recipes/add.php");
                            exit;
                    }
                }
    


                //instancasr recetas y guardar la base de datos
                $this->recetaModel = new Recipe(
                    $_SESSION['user'],
                    $titulo,
                    $descripcion,
                    $pasos,
                    $tiempo,
                    $uniqueName,
                    $tipo_comida // Pasar el tipo de comida
                );

                $id = $this->recetaModel->save();



                //isntancias de ingredientes y guardar la base de datos
                $this->ingredientModel = new Ingredient(
                    $id,
                    $ingredientes
                );
                $this->ingredientModel->save();


    
                    $_SESSION['mensaje'] = 'se insertaron correctamente los datos';
                    return header("Location: " . BASE_URL . "/views/recipes/list.php");
                }else {
                    $errors = $this->validator->getErrors();
                    var_dump($errors);
                }
                
            }  catch (Exception $e) {
                // Manejar errores y redirigir con un mensaje
                $_SESSION['mensaje'] = $e->getMessage();
                header("Location: " . BASE_URL . "/views/recipes/add.php");
                exit;
            }
            
        }

        public function actualizarReceta($data, $file)
        {
            $datosCombinados = array_merge($_POST, $_FILES);
            session_start();
            try {
                // Reglas de validación
                $rules = [
                    'id' => [
                        'required' => true,
                        'int' => true
                    ],
                    'titulo' => [
                        'required' => true,
                        'string' => true,
                        'max' => 200
                    ],
                    'descripcion' => [
                        'required' => true,
                        'string' => true,
                        'max' => 2000
                    ],
                    'pasos' => [
                        'required' => true,
                        'string' => true,
                        'max' => 2000
                    ],
                    'tiempo' => [
                        'required' => true,
                        'string' => true,
                        'max' => 50
                    ],
                    'ingrediente' => [
                        'required' => true,
                        'array' => true,
                        'minItems' => 1
                    ],
                'tipo_comida' => [
                    'required' => true,
                    'enum' => ['Desayunos', 'Almuerzos', 'Cenas']
                ]
                    

                ];

                if(!empty($file['imagen']['name'])) {
                    $rules += [
                        'imagen' => [
                            'required' => false,
                            'image' => true
                        ]
                        ];
                }
        
                if ($this->validator->validate($datosCombinados, $rules)) {
                    $id = $data['id'];
                    $titulo = $data['titulo'];
                    $descripcion = $data['descripcion'];
                    $pasos = $data['pasos'];
                    $tiempo = $data['tiempo'];
                    $ingredientes = $data['ingrediente'];
                    $imagen = $file['imagen'];
                    $tipo_comida = $data['tipo_comida'];
                

                    
                    // Verificar si el usuario está autenticado
                    if (!isset($_SESSION['user'])) {
                        return header('location: ' . BASE_URL . '/views/auth/login.php');
                    }
                    
                    // Subir imagen (si existe)
                    $recipeImg = $this->recetaModel->getById($id); // Obtener los datos de la receta por ID
                    $uniqueName = null;
                    
                    // Validar que existe una imagen actual
                    if(!empty(!empty($file['imagen']['name']))) {

                        // Manejar la nueva imagen
                        if (!empty($imagen['tmp_name'])) {
                            try {
                                $uniqueName = $this->converter->uploadImage($imagen, $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/public/img/');

                                //ELIMINAR el archivo anterior
                                if (!empty($recipeImg[0]['imagen'])) {
                                    $currentImage = $recipeImg[0]['imagen'];
                                
                                    // Verificar si el archivo de la imagen existe en el servidor
                                    $imagePath = $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/public/img/' . $currentImage;
                                
                                    if (file_exists($imagePath)) {
                                        // Eliminar la imagen existente
                                        unlink($imagePath);
                                        echo "Imagen eliminada: $imagePath"; // Mensaje de depuración
                                    } else {
                                        echo "La imagen no existe: $imagePath"; // Mensaje de depuración
                                    }
                                } else {
                                    echo "No hay imagen para eliminar."; // Mensaje de depuración
                                }
                            } catch (Exception $e) {
                                    // Manejar errores y redirigir con un mensaje
                                    $_SESSION['mensaje'] = $e->getMessage();
                                    header("Location: " . BASE_URL . "/views/recipes/add.php");
                                    exit;
                            }
                        } else {
                            echo "No se cargó ninguna nueva imagen."; // Mensaje de depuración
                        }

                        // Instanciar modelo de receta y actualizar en la base de datos
                        $this->recetaModel = new Recipe(
                            $_SESSION['user'],
                            $titulo,
                            $descripcion,
                            $pasos,
                            $tiempo,
                            $uniqueName,
                            $tipo_comida
                        );
                        
                    
                        $this->recetaModel->update($id);
                    }else {
                            // Instanciar modelo de receta y actualizar en la base de datos
                            $this->recetaModel = new Recipe(
                                $_SESSION['user'],
                                $titulo,
                                $descripcion,
                                $pasos,
                                $tiempo
                               
                            );
                            $this->recetaModel->setTipoComida($tipo_comida);
                            
                        
                            $this->recetaModel->updateNoImg($id);
                    }


                    


                    

                    //elimianr todos los ingredientes de esa receta
                    $this->ingredientModel->deleteById($id);

                    
                    // Actualizar los ingredientes en la base de datos
                    $this->ingredientModel = new Ingredient(
                        $id, 
                        $ingredientes
                    );
                    
                    $this->ingredientModel->save();
        
                    // Redirigir con un mensaje de éxito
                    $_SESSION['mensaje'] = 'Receta actualizada correctamente';
                    return header("Location: " . BASE_URL . "/views/recipes/list.php");
                } else {
                    // Manejar errores de validación
                    $errors = $this->validator->getErrors();
                    var_dump($errors);
                }
            } catch (Exception $e) {
                // Manejar errores generales y redirigir con un mensaje
                $_SESSION['mensaje'] = $e->getMessage();
                header("Location: " . BASE_URL . "/views/recipes/edit.php?id=" . ($data['id'] ?? ''));
                //exit;
            }
        }

        public function borrarReceta($data) {
            try {
                $id = $data['valorFinal'];
                $this->recetaModel = new Recipe();
                $this->ingredientModel = new Ingredient();

                //eliminar la iamgen del servidor
                $recipeImg = $this->recetaModel->getById($id); // Obtener los datos de la receta por ID
                if (!empty($recipeImg[0]['imagen'])) {
                    $currentImage = $recipeImg[0]['imagen'];
                
                    // Verificar si el archivo de la imagen existe en el servidor
                    $imagePath = $_SERVER['DOCUMENT_ROOT'] . BASE_URL . 'public/img/' . $currentImage;
                
                    if (file_exists($imagePath)) {
                        // Eliminar la imagen existente
                        unlink($imagePath);
                        echo "Imagen eliminada: $imagePath"; // Mensaje de depuración
                    } else {
                        throw new Exception("La imagen no existe en el servidor" . $imagePath);
                    }
                } else {
                    throw new Exception("no hay imagen para borrar");

                }

                
                $this->recetaModel->deleteById($id);
                //si no tiene el on delete cascade
                //$this->ingredientModel->deleteById($id);
                
                $_SESSION['mensaje'] = 'Receta actualizada correctamente';
                return header("Location: " . BASE_URL . "/views/recipes/list.php");
            } catch (Exception $e) {
                // Manejar errores generales y redirigir con un mensaje
                $_SESSION['mensaje'] = $e->getMessage();
                return header("Location: " . BASE_URL . "/views/recipes/list.php");
            }
        }
        
        public function getAllRecipe() {
            try {
                $recipes = Recipe::getAll();

                
                
                // Añadir ingredientes a cada receta
                foreach ($recipes as &$recipe) { // Referencia (&) para modificar directamente el arreglo
                    $ingredients = Ingredient::getByRecetaId($recipe['id']); // Obtener ingredientes de la receta
                    $recipe['ingrediente'] = $ingredients; // Añadir ingredientes al arreglo de la receta
                }

                return json_encode($recipes);
                } catch (Exception $e) {

                    return json_encode($e->getMessage());
                }
            }

    public function getRecipe($id)
    {
        try {
            $recipes = Recipe::getByUserId($id);

          // Añadir ingredientes a cada receta
            foreach ($recipes as &$recipe) { // Referencia (&) para modificar directamente el arreglo
                $ingredients = Ingredient::getByRecetaId($recipe['id']); // Obtener ingredientes de la receta
                $recipe['ingrediente'] = $ingredients; // Añadir ingredientes al arreglo de la receta
            }

            // Devolver la respuesta en formato JSON
            return json_encode($recipes);
        } catch (Exception $e) {
            return json_encode($e->getMessage());
        }
    }

    public function getRecipeDetail($id)
    {
        $id = $id['recipe_id'];
        try {
            $recipes = Recipe::getById($id);
            $ingredient = Ingredient::getByRecetaId($id);

            return json_encode([$recipes, $ingredient]);
        } catch (Exception $e) {

            return json_encode($e->getMessage());
        }
    }

    public function buscarRecetas($data)
    {
        try {
            $filters = [
                'titulo' => $data['titulo'] ?? null,
                'tipo_comida' => $data['tipo_comida'] ?? null,
                'ingrediente' => $data['ingrediente'] ?? null,
                'tiempo' => $data['tiempo'] ?? null,
            ];

            $recipes = Recipe::search($filters);

            return json_encode($recipes);
        } catch (Exception $e) {
            return json_encode(['error' => $e->getMessage()]);
        }
    }
}


/*
    *
    * Procesar solicitud del form de login
    *
    */
    $controller = new RecipeController();
    //manejo de post
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if(isset($_POST['action'])) {
            if ($_POST['action'] === 'register') {
                $controller->guardarReceta($_POST, $_FILES);
            }
            if($_POST['action'] === 'update') {
                $controller->actualizarReceta($_POST, $_FILES);
            }
        }
        if(isset($_POST['valorFinal'])) {
            $controller->borrarReceta($_POST);
        }
    }  
    
    
        
