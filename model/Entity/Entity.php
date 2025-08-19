<?php

class StandardTypeEntity {
    private int $id;
    private String $name;
    private String $description;
}

class StandardEntity {
    private int $id;
    private String $name;
    private String $description;
    private String $version;
    private int $typeId; // FK -> StandardTypes.Id
    private StandardEntityRequirementEntity $requirement = [];
}

class StandardRequirementEntity {
    private int $id;
    private int $standardId; // FK -> Standards.Id
    private String $name;
    private String $description;
}

class RiskEntity {
    private int $id;
    private String $name;
    private String $icon;
    private String $description;
}

class RiskEvaluationEntity {
    private int $id;
    private Date $date;
    private String $institution;
    private AdminTasks $adminTasks = [];
}

class AdminTaskEntity {
    private int $id;
    private String $name;
    private String $description;
    private RequirementEntity $requirements = [];
}

class RequirementEntity {
    private int $id;
    private String $name;
    private String $description;
    private int $standardId;            // FK -> Standards.Id
    private int $standardRequirementId; // FK -> StandardRequirements.Id
    private String $compliance;         // ENUM: Yes, No, Not Applicable
    private RiskAssessmentEntity $assessments = [];
}


class RiskAssessmentEntity {
    private int $riskId;        // FK -> Risks.Id
    private String $assessment; // ENUM: Primary, Secondary
}

