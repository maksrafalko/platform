<?php

namespace Oro\Bundle\ImapBundle\Form\Type;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\Model\AccountTypeModel;
use Oro\Bundle\ImapBundle\Form\Type\ConfigurationGmailType;
use Oro\Bundle\ImapBundle\Form\Type\ConfigurationType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ChoiceAccountType extends AbstractType
{
    const NAME = 'oro_imap_choice_account_type';

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(
        TranslatorInterface $translator
    ) {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('accountType', ChoiceType::class, [
            'label' => $this->translator->trans('oro.imap.configuration.account_type.label'),
            'tooltip'  => 'oro.imap.configuration.tooltip',
            'required' => false,
            // TODO: remove 'choices_as_values' option below in scope of BAP-15236
            'choices_as_values' => true,
            'choices' => [
                $this->translator->trans(
                    'oro.imap.configuration.account_type.' . AccountTypeModel::ACCOUNT_TYPE_NO_SELECT
                ) => '',
                $this->translator->trans(
                    'oro.imap.configuration.account_type.' . AccountTypeModel::ACCOUNT_TYPE_GMAIL
                ) => AccountTypeModel::ACCOUNT_TYPE_GMAIL,
                $this->translator->trans(
                    'oro.imap.configuration.account_type.' . AccountTypeModel::ACCOUNT_TYPE_OTHER
                ) => AccountTypeModel::ACCOUNT_TYPE_OTHER,
            ],
        ]);

        $this->initEvents($builder);
    }

    /**
     * @param FormEvent $formEvent
     */
    public function preSubmit(FormEvent $formEvent)
    {
        $form = $formEvent->getForm();
        $data = $formEvent->getData();

        if (null === $data) {
            return;
        }
        $accountTypeModel = $this->createAccountTypeModelFromData($data);

        if ($accountTypeModel === null) {
            //reset data for avoiding form extra parameters error
            $formEvent->setData(null);
            return;
        } elseif ($form->getData() && $form->getData()->getAccountType() !== $data['accountType']) {
            //set data here, for renew viewData of the form
            $form->setData($accountTypeModel);
        }

        if ($accountTypeModel instanceof AccountTypeModel) {
            $this->updateForm($form, $accountTypeModel);
        }
    }

    /**
     * @param FormEvent $formEvent
     */
    public function preSetData(FormEvent $formEvent)
    {
        $accountTypeModel = $formEvent->getData();
        $form = $formEvent->getForm();

        if ($accountTypeModel instanceof AccountTypeModel) {
            $this->updateForm($form, $accountTypeModel);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => 'Oro\\Bundle\\ImapBundle\\Form\\Model\\AccountTypeModel']);
    }

    /**
     * Update form if accountType is changed
     *
     * @param FormBuilderInterface $builder
     */
    protected function initEvents(FormBuilderInterface $builder)
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit']);
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
    }

    /**
     * @param FormInterface $form
     * @param AccountTypeModel $accountTypeModel
     */
    protected function updateForm(FormInterface $form, AccountTypeModel $accountTypeModel)
    {
        if ($accountTypeModel instanceof AccountTypeModel) {
            if ($accountTypeModel->getAccountType() === AccountTypeModel::ACCOUNT_TYPE_OTHER) {
                $form->add('userEmailOrigin', ConfigurationType::class);
            }

            if ($accountTypeModel->getAccountType() === AccountTypeModel::ACCOUNT_TYPE_GMAIL) {
                $form->add('userEmailOrigin', ConfigurationGmailType::class);
            }
        }
    }

    /**
     * Create object of AccountTypeModel using data of form
     *
     * @param array $data
     *
     * @return AccountTypeModel|null
     */
    protected function createAccountTypeModelFromData($data)
    {
        $imapGmailConfiguration = isset($data['userEmailOrigin']) ? $data['userEmailOrigin'] : [];

        if (empty($imapGmailConfiguration['user'])) {
            return null;
        }
        $accountTypeModel =  new AccountTypeModel();
        $accountTypeModel->setAccountType($data['accountType']);

        $userEmailOrigin = new UserEmailOrigin();
        $userEmailOrigin->setImapHost($imapGmailConfiguration['imapHost']);
        $userEmailOrigin->setImapPort($imapGmailConfiguration['imapPort']);
        $userEmailOrigin->setImapEncryption($imapGmailConfiguration['imapEncryption']);

        $userEmailOrigin->setUser($imapGmailConfiguration['user']);

        if (!empty($imapGmailConfiguration['accessTokenExpiresAt'])) {
            $newExpireDate = $imapGmailConfiguration['accessTokenExpiresAt'];
            if (!$newExpireDate instanceof \Datetime) {
                $utcTimeZone = new \DateTimeZone('UTC');
                $accessTokenExpiresAt = $imapGmailConfiguration['accessTokenExpiresAt'];
                $newExpireDate = new \DateTime('+' . $accessTokenExpiresAt . ' seconds', $utcTimeZone);
            }

            $userEmailOrigin->setAccessTokenExpiresAt($newExpireDate);
        }

        $accountTypeModel->setUserEmailOrigin($userEmailOrigin);

        return $accountTypeModel;
    }
}
