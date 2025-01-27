<?php

namespace App\Security\Voter;

use App\Entity\Products;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use \Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ProductsVoter extends Voter
{
    const EDIT = 'PRODUCT_EDIT';
    const DELETE = 'PRODUCT_DELETE';

    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, $product): bool
    {
        if(!in_array($attribute, [self::EDIT, self::DELETE])) {
            return false;
        }

        if(!$product instanceof Products) 
            {
                return false;
            }
            return true;

        //return in_array($attribute, [self::EDIT, self::DELETE]) && $product instanceof Products;

            
        
    }

    protected function voteOnAttribute($attribute, $product, TokenInterface $token): bool
    {
       // on récupère l-utilisateur à partir du token 
       $user = $token->getUser();

       if(!$user instanceof UserInterface) return false;
       
       // on vérifie si la utilisateur est admin

       if($this->security->isGranted('ROLE_ADMIN')) return true;

       // on va verifie la permissions
       switch($attribute){
        case self::EDIT:
            // on vérifie si l'utilisateur peut éditer
            return $this->canEdit();
            break;

        case self::DELETE:
            // on vérifie si l'utilisateur peut supprimer
            return $this->canDelete();
            break;    

       }
    }

    // si est Roled product admin il peut 

    private function canEdit(){
        return $this->security->isGranted('ROLE_PRODUCT_ADMIN');
    }
    private function canDelete(){
        return $this->security->isGranted('ROLE_ADMIN');
    }
}