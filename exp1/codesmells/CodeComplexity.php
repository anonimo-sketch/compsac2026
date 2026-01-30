<?php

/**
 * Code Smell: Code Complexity
 * Descrição: Código com complexidade excessiva, indicando possível responsabilidade excessiva
 */

function evaluateUser($user)
{
    if (!$user->isActive()) {
        return "Invalid";
    }

    if (!$user->hasSubscription()) {
        return "Invalid";
    }

    $subscription = $user->getSubscription();

    if (!$subscription) {
        return "Invalid";
    }

    if (!$subscription->isValid()) {
        return "Inactive";
    }

    if ($subscription->getExpirationDate() < new DateTime()) {
        return "Expired";
    }

    if ($subscription->getPlan() === "trial" && $subscription->getDaysLeft() < 3) {
        return "Trial Expiring Soon";
    }

    if ($subscription->getPlan() === "premium" && $subscription->getFeatures()->count() < 5) {
        return "Limited Premium";
    }

    return "Active";
}
