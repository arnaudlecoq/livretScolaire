<?php

//********************************************
//********* Récupération des données *********
//********************************************
$sxe = simplexml_load_string(mb_convert_encoding(file_get_contents('livret.xml'),"ISO-8859-15","ISO-8859-15"));
$sxe->entete->etablissement = getSettingValue("gepiSchoolRne");

$niveaux = niveauConcernees($anneeSolaire, $selectClasses);
$classes = classesConcernees($anneeSolaire, $selectClasses);
$eleves = elevesConcernees($anneeSolaire, $selectClasses);

$eleves2 = $eleves;
while($eleve = $eleves2->fetch_object()){
	$lastNiveau="";
	$newElv = $sxe->donnees->addChild('eleve');
	$newElv->addAttribute('id', $eleve->ine);
	//$newElv->addAttribute('nom', $eleve->nom);
	//$newElv->addAttribute('prenom', $eleve->prenom);
	
	// récupérer les engagements
	/*
	$listeEngagements = engagementsEleve($eleve->ine);
	$engagementAutre = engagementAutreEleve($eleve->ine);
	
	if(($listeEngagements->num_rows > 0) || ($engagementAutre->num_rows > 0)) {
		$engagements = $newElv->addChild('engagements');
		while ($listeEngagement = $listeEngagements->fetch_object()){
			$newEngagement = $engagements->addChild('engagement');
			$newEngagement->addAttribute('code',$listeEngagement->code_engagement );
		}
		while ($autre = $engagementAutre->fetch_object()){
			$newEngagement = $engagements->addChild('engagement-autre',$autre->engagement);
		}
	}
	*/
	
	// récupérer l'avis d"examen
	/*
	$avisEleve=avisEleve($eleve->ine);
	if($avisEleve->num_rows > 0) {
		$newAvisElv=$newElv->addChild('avisExamen');
		while ($avis = $avisEleve->fetch_object()){
			$newAvisElv->addAttribute('code',$avis->avis );
		}
	}
	*/
	
	// récupérer les scolarités
	$scolarites = $newElv->addChild('scolarites');
	$annees = anneesEleve($eleve->ine);
	while ($annee = $annees->fetch_object()){
		
		// On récupère le niveau
		$niveau = "";
		$getNiveau = getNiveau($annee->annee, $annee->id_classe);
		$niveau = $getNiveau->fetch_object();
		if ($niveau && $lastNiveau != $niveau->apb_niveau) {
			$newScolarite = $scolarites->addChild('scolarite');
			$newScolarite->addAttribute('annee-scolaire',$annee->annee);
			$codePeriode=codePeriode($eleve->ine, $annee->annee);
			$newScolarite->addAttribute('code-periode',$codePeriode);
		
			//$newScolarite->addAttribute('niveau',$niveau->apb_niveau);
			//$newScolarite->addAttribute('classe',$niveau->id);
			
			$lastNiveau = $niveau->apb_niveau;
			
			// récupérer les investissements
			/*
			$investissements = avisInvestissement($eleve->ine, $annee->annee);
			if ($investissements->num_rows){
				$investissement = $investissements->fetch_object();
				$newInvestissement = $newScolarite->addChild('avisInvestissement',$investissement->avis);
				$newInvestissement->addAttribute('date',$investissement->date);
				$newInvestissement->addAttribute('nom',$investissement->nom);
				$newInvestissement->addAttribute('prenom',$investissement->prenom);
			}
			 */
			
			$nbPeriode = 0;
			if ($newScolarite["code-periode"] == "T") {
				$nbPeriode = 3;
			} elseif ($newScolarite["code-periode"] == "S") {
				$nbPeriode = 2;
			}
			
			// TODO récupérer les avis Chef Etablissement
			// $newScolarite->addChild('avisChefEtab');
			
			// TODO récupérer les avis Engagement
			// $newScolarite->addChild('avisEngagement');
						
			// récupérer les évaluations
			$newEvaluations = evaluations($eleve->ine,$annee->annee);
			
			while ($evaluation = $newEvaluations->fetch_object()){
				
				$compteEleves = compteElvEval($annee->annee, $evaluation->code_service);
				$compteElv = $compteEleves->fetch_object();
				if ($compteElv->nombre){

					$newEval = $newScolarite->addChild('evaluation');
					$newEval->addAttribute('modalite-election',$evaluation->modalite);
					//$newEval->addAttribute('code-gepi',$evaluation->code_service);
					$newEval->addAttribute('code-matiere',str_pad($evaluation->code_sconet, 6, '0', STR_PAD_LEFT));
					//$newEval->addAttribute('libelle',  rtrim($evaluation->libelle_sconet));

					// TODO récupérer les dates de validation
					//$newEval->addAttribute('date','');

					$newStructure = $newEval->addChild('structure');

					$structureEvaluation = structureEval($annee->annee, $evaluation->code_service);
					$structureEval = $structureEvaluation->fetch_object();
					$moinsHuit = reparMoinsHuit($annee->annee, $evaluation->code_service, $compteElv->nombre);
					$huitDouze = reparMoinsHuit($annee->annee, $evaluation->code_service, $compteElv->nombre, 8, 12);
					$plusDouze = reparMoinsHuit($annee->annee, $evaluation->code_service, $compteElv->nombre, 12, 21);

					$newStructure->addAttribute('effectif',$compteElv->nombre);
					$newStructure->addAttribute('moyenne',round($structureEval->moyenne,2));				
					$newStructure->addAttribute('repar-moins-huit',$moinsHuit);				
					$newStructure->addAttribute('repar-huit-douze',$huitDouze);			
					$newStructure->addAttribute('repar-plus-douze',$plusDouze);
					
					$structureEvaluation ->close();
					
					// TODO récupérer l'appréciation annuelle
					// $appAnnuelle="";
					// $newEval->addChild('annuelle', $appAnnuelle);

					$Periodiques = $newEval->addChild('periodiques');
					$moyennes = moyenneTrimestre($annee->annee, $evaluation->code_service, $eleve->ine);
					while ($moyenne = $moyennes->fetch_object()) {
						$trimestre = $Periodiques->addChild('periode');
						$trimestre->addAttribute('numero', $moyenne->trimestre);
						if ("S" == $moyenne->etat) {
							$trimestre->addAttribute('moyenne', $moyenne->moyenne);
						} else {
							$trimestre->addAttribute('moyenne', -1);
						}
					}
					$moyennes->close();
					// TODO récupérer les compétences
					// $newEval->addChild('competences');

					// TODO récupérer les enseignants
					$Enseignants = $newEval->addChild('enseignants');
					$getEnseignants = enseignants($annee->annee, $evaluation->code_service);
					while ($getEnseignant = $getEnseignants->fetch_object()) {
						$Enseignant = $Enseignants->addChild('enseignant');

						$Enseignant->addAttribute('nom', substr($getEnseignant->nom, 0,65));
						$Enseignant->addAttribute('prenom', substr($getEnseignant->prenom, 0,50));
					}
					$getEnseignants->close();
				}
				$compteEleves->close();				
			}
			$newEvaluations->close();			
		}
		//$getNiveau->close();		
	}
	//$engagementAutre->close();
	//$listeEngagements->close();
	//$avisEleve->close();
	//$investissements->close();
	$annees->close();
}

echo $dirTemp."essai.xml";
   
$sxe->asXML($dirTemp."essai.xml");