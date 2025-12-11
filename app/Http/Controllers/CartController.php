<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\CartItem;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * Affiche le panier
     */
    public function index()//retoune l'utilisateur connecter
    {
        $cart = auth()->user()->getOrCreateCart();//recupere le panier de l'utilisateur connecter ou en cree un nouveau s'il n'en a pas
        $cart->load(['items.product.category']);//charge les relations necessaire pour afficher les produits dans le panier

        return view('cart.index', compact('cart'));//retourne la vue du panier avec les donnees du panier
    }

    /**
     * Ajoute un produit au panier
     */
    public function add(Product $product)//recupere le produit a ajouter au panier
    {
        // Vérifie que le produit est disponible
        if (!$product->in_stock) {//verifie si le produit est en stock
            return back()->with('error', 'Ce produit n\'est plus en stock.');//retourne a la page precedente avec un message d'erreur
        }
        $cart = auth()->user()->getOrCreateCart();//recupere le panier de l'utilisateur connecter ou en cree un nouveau s'il n'en a pas


        // Vérifie si le produit est déjà dans le panier
        $cartItem = $cart->items()->where('product_id', $product->id)->first();

        if ($cartItem) {//si le produit n'est pas en stock affiche un message d'erreur

            // Incrémente la quantité si déjà présent
            $cartItem->incrementQuantity();//incremente la quantite de l'item dans le panier

            $message = 'Quantité mise à jour dans votre panier.';//message de confirmation

        } else {//si l'item n'est pas present dans le panier
            
            // Ajoute un nouvel item
            $cart->items()->create([//cree un nouvel item dans le panier
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => $product->sale_price ?? $product->price,
            ]);
            $message = 'Produit ajouté à votre panier.';//message de confirmation
        }

        return back()->with('success', $message);//retourne a la page precedente avec un message de confirmation
    }

    /**
     * Met à jour la quantité d'un item
     */
    public function update(Request $request, CartItem $cartItem)
    {
        // Vérifie que l'item appartient bien au panier de l'utilisateur
        if ($cartItem->cart->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'quantity' => 'required|integer|min:1|max:99',
        ]);

        // Vérifie le stock disponible
        if ($request->quantity > $cartItem->product->stock_quantity) {
            return back()->with('error', 'Stock insuffisant pour cette quantité.');
        }

        $cartItem->update([
            'quantity' => $request->quantity,
        ]);

        return back()->with('success', 'Quantité mise à jour.');
    }

    /**
     * Retire un item du panier
     */
    public function remove(CartItem $cartItem)
    {
        // Vérifie que l'item appartient bien au panier de l'utilisateur
        if ($cartItem->cart->user_id !== auth()->id()) {
            abort(403);
        }

        $cartItem->delete();

        return back()->with('success', 'Produit retiré du panier.');
    }

    /**
     * Vide complètement le panier
     */
    public function clear()
    {
        $cart = auth()->user()->cart;

        if ($cart) {
            $cart->clear();
        }

        return back()->with('success', 'Panier vidé.');
    }
}