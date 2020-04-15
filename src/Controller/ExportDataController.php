<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use DateTime;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
// use App\Entity\Category;

// Include PhpSpreadsheet required namespaces
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ExportDataController extends AbstractController
{
    /**
     * @Route("/export-all-data", name="export_data")
     * @IsGranted("ROLE_ADMIN")
     */
    public function data_export_for_backup()
    {        
        // return new Response(var_dump($idsMatieresNiveau));
        $em = $this->getDoctrine()->getManager();
        $tables = $em->getConnection()->getSchemaManager()->listTables();        
        $spreadsheet = new Spreadsheet();
        $index = 0;
        foreach ($tables as $table) {
          // $table = $tables[0];
          $index++;
          $classeName = $table->getName();
          $repository = "App\\Entity\\".ucwords($classeName);
          $repository = $this->camelCase($repository);
          if(class_exists($repository))
          {
            $entityGetters = $this->allGetters($table->getColumns());
            if($index == 1)
              $sheet = $spreadsheet->getActiveSheet();
            else
              $sheet = $spreadsheet->createSheet();
            
            $letter = "A";
            $number = 1;
            foreach ($table->getColumns() as $key => $value) {
              $sheet->setCellValue($letter.$number, $key);
              $letter++;
            }
            
            $number = 2;
            $data = $this->getDoctrine()->getManager()->getRepository($repository)->findAll();
            // $data = $manager->getRepository($classeName::class)->findAll();
            for ($i=0; $i < count($data); $i++) { 
              $letter = "A";
              foreach ($entityGetters as $getter) {
                # code...
                // dd($data[$i]);
                if(method_exists($data[$i], $getter)){
                  $getterValue = $data[$i]->$getter();
                  if($getterValue instanceof DateTime){
                    $getterValue = $getterValue->format("Y-m-d H:i:s");
                  }
                  elseif(is_object($getterValue) and !($getterValue instanceof DateTime)){
                    // dump(get_class($data[$i]));
                    if(method_exists($getterValue, 'getId'))
                      $getterValue = $getterValue->getId();
                    else
                      $getterValue = "Inconnu";
                  }
                  elseif(is_array($getterValue)){
                    $getterValue = implode(",", $getterValue);
                  }
                  $sheet->setCellValue($letter.$number, $getterValue);
                  $letter++;
                }
              }
              $number++;
            }
            $sheet->setTitle($classeName);
            // if($index != 1)
              // $sheet->addSheet($spreadsheet);
            
          }
        }
        // Create your Office 2007 Excel (XLSX Format)
        $writer = new Xlsx($spreadsheet);
        
        // Create a Temporary file in the system
        $fileName = 'backup-smart-market-'.(new DateTime())->format("d-m-Y").'.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        
        // Create the excel file in the tmp directory of the system
        $writer->save($temp_file);
        
        // Return the excel file as an attachment
        $backup = $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);
        if($backup->getStatusCode() == 200){
          $this->addFlash('success', 'Importation réussie avec succès.');
          return $backup;
        }
        // $table = $tables[0];
        dd($entityGetters);
        dd($classeName);
        
        
        // /* @var $sheet \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet */
        // // Create a new worksheet called "My Data"
        // $myWorkSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'My Data');
        
        // // Attach the "My Data" worksheet as the first worksheet in the Spreadsheet object
        // $spreadsheet->addSheet($myWorkSheet, 0);

    }



    public function allGetters(array $tableColumns)
    {
      /**
       * Cette fonction me permet de générer la liste des getters d'une entité en me basant sur les noms des champs de la table liée en base de données.
       * Elle prend un seul paramètre de type array. C'est en réalité le nom des champs tels qu'ils sont en base de données.
       */
      $getters = [];
      foreach ($tableColumns as $key => $value) {
        $getter = $this->camelCase($key);
        if(strlen($getter) !== 2 and substr($getter, -2) == "Id"){
          $getter = substr($getter, 0, -2);
        }
        $column = "get".(ucwords($getter));
        $getters[] = $column;
      }
      return $getters;
    }

    public function camelCase($word)
    {
      /**
       * Pour chaque nom de champs, on parcourt la chaine de caractères
       * Si le caractère que nous rencontrons est unserscore (_) alors on l'ignore et le caractère suivant est mis en majuscule de sorte à obtenir un getter 
       * Au final avec un champs comme "created_by_id" on aura un getter qui sera "getCreatedBy"
       */
      $getter = "";
      $continous = true;
      for ($i=0; $i < strlen($word); $i++) {
        if ($word[$i] === "_"){
          $letter = "";
          $continous = false;
        }
        else if ($word[$i] !== "_" and $continous === false) {
          $letter = strtoupper($word[$i]);
          $continous = true;
        }
        else if ($word[$i] !== "_" and $continous === true) {
          $letter = $word[$i];
          $continous = true;
        }
        $getter = $getter.$letter;
      }

      return $getter;
    }
  }
