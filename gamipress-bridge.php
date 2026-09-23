<?php
/*
   Copyright (C) 2026  Lorenzo Armezzani
   Plugin Name: App GamiPress Bridge
   Description: Endpoint REST API leggeri e sicuri per l'integrazione tra l'App mobile e GamiPress (Punti, Badge, Casi Chiusi e Profilo Utente).
   Version: 1.1
   IA: Gemini assisted
   
   This program is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with this program.  If not, see <https://www.gnu.org/licenses/>. 
*/


if ( ! defined( 'ABSPATH' ) ) {
    exit; // Blocco accesso diretto
}

class App_GamiPress_Bridge {

    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Registrazione degli Endpoint REST API
     */
    public function register_routes() {
        $namespace = 'app-gamipress/v1';

        // 1. Endpoint GET: Profilo Utente Completo (Punti, Rank, Badge, Casi Chiusi)
        register_rest_route( $namespace, '/profilo_me', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_profilo_me' ),
            'permission_callback' => array( $this, 'check_auth' ),
        ) );

        // 2. Endpoint POST: Assegna Punti
        register_rest_route( $namespace, '/assegna_punti', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'assegna_punti' ),
            'permission_callback' => array( $this, 'check_auth' ),
        ) );

        // 3. Endpoint POST: Assegna Badge
        register_rest_route( $namespace, '/assegna_badge', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'assegna_badge' ),
            'permission_callback' => array( $this, 'check_auth' ),
        ) );

        // 4. Endpoint POST: Assegna Achievement (Casi Chiusi)
        register_rest_route( $namespace, '/assegna_achievement', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'assegna_achievement' ),
            'permission_callback' => array( $this, 'check_auth' ),
        ) );
    }

    /**
     * Controllo Sicurezza: Verifica che l'utente sia autenticato
     */
    public function check_auth() {
        return is_user_logged_in();
    }

    /**
     * GET /app-gamipress/v1/profilo_me
     * Recupera in un'unica chiamata tutti i dati del giocatore loggato
     */
    public function get_profilo_me( $request ) {
        $user_id = get_current_user_id();
        $user    = get_userdata( $user_id );

        // 1. Punti (Punti Detective)
        $punti_detective = gamipress_get_user_points( $user_id, 'detective' );

        // 2. Rank attuale
        $user_rank = gamipress_get_user_rank( $user_id, 'detective' );
        $rank_info = array(
            'id'    => $user_rank ? $user_rank->ID : null,
            'title' => $user_rank ? $user_rank->post_title : 'Nessun Rank'
        );

        // 3. Badge ottenuti
        $user_badges = gamipress_get_user_achievements( array(
            'user_id'   => $user_id,
            'post_type' => 'badges',
        ) );

        $badges_list = array();
        if ( ! empty( $user_badges ) ) {
            foreach ( $user_badges as $b ) {
                $badges_list[] = array(
                    'id'    => $b->post_id,
                    'title' => get_the_title( $b->post_id ),
                    'date'  => $b->date
                );
            }
        }

        // 4. Casi Chiusi ottenuti
        $user_cases = gamipress_get_user_achievements( array(
            'user_id'   => $user_id,
            'post_type' => 'casi-chiusi',
        ) );

        $cases_list = array();
        if ( ! empty( $user_cases ) ) {
            foreach ( $user_cases as $c ) {
                $cases_list[] = array(
                    'id'    => $c->post_id,
                    'title' => get_the_title( $c->post_id ),
                    'date'  => $c->date
                );
            }
        }

        return new WP_REST_Response( array(
            'success'           => true,
            'user_id'           => $user_id,
            'user_display_name' => $user->display_name,
            'punti'             => array(
                'detective' => $punti_detective
            ),
            'rank'              => $rank_info,
            'badge'             => $badges_list,
            'casi_chiusi'       => $cases_list
        ), 200 );
    }

    /**
     * POST /app-gamipress/v1/assegna_punti
     */
    public function assegna_punti( $request ) {
        $user_id     = get_current_user_id();
        $points_type = sanitize_text_field( $request->get_param( 'points_type' ) );
        $points      = intval( $request->get_param( 'points' ) );

        if ( empty( $points_type ) || $points <= 0 ) {
            return new WP_Error( 'invalid_data', 'Inserire un tipo di punti valido e un valore superiore a 0.', array( 'status' => 400 ) );
        }

        gamipress_award_points_to_user( $user_id, $points, $points_type );

        $new_total = gamipress_get_user_points( $user_id, $points_type );

        return new WP_REST_Response( array(
            'success'     => true,
            'message'     => 'Punti assegnati con successo!',
            'user_id'     => $user_id,
            'points_type' => $points_type,
            'points'      => $points,
            'new_total'   => $new_total
        ), 200 );
    }

    /**
     * POST /app-gamipress/v1/assegna_badge
     */
    public function assegna_badge( $request ) {
        $user_id  = get_current_user_id();
        $badge_id = intval( $request->get_param( 'badge_id' ) );

        if ( ! $badge_id ) {
            return new WP_Error( 'missing_id', 'ID Badge mancante o non valido.', array( 'status' => 400 ) );
        }

        gamipress_award_achievement_to_user( $badge_id, $user_id );

        return new WP_REST_Response( array(
            'success'  => true,
            'message'  => 'Badge assegnato con successo!',
            'user_id'  => $user_id,
            'badge_id' => $badge_id
        ), 200 );
    }

    /**
     * POST /app-gamipress/v1/assegna_achievement
     */
    public function assegna_achievement( $request ) {
        $user_id        = get_current_user_id();
        $achievement_id = intval( $request->get_param( 'achievement_id' ) );

        if ( ! $achievement_id ) {
            return new WP_Error( 'missing_id', 'ID Achievement mancante o non valido.', array( 'status' => 400 ) );
        }

        gamipress_award_achievement_to_user( $achievement_id, $user_id );

        return new WP_REST_Response( array(
            'success'        => true,
            'message'        => 'Achievement assegnato con successo!',
            'user_id'        => $user_id,
            'achievement_id' => $achievement_id
        ), 200 );
    }
}

// Inizializza il plugin
new App_GamiPress_Bridge();