<h2>택배사별 송장 확인</h2>


<?php
/** @var array $agents */
foreach ( $agents as $agent_slug => $test_numbers ) : ?>

  <?php $agent = WSKL_Agent_Helper::get_tracking_number_agent_by_slug( $agent_slug ); ?>

  <?php if ( $agent ) : ?>

    <h3><?php echo $agent->get_name(); ?></h3>
    <ul>
      <?php foreach ( $test_numbers as $t ) : ?>
        <li>
          <a href="<?php echo esc_attr( $agent->get_url_by_tracking_number( $t ) ); ?>" target="_blank">
            <?php echo $t; ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>

  <?php else : ?>
    <h3>Slug <?= $agent_slug ?> not found!</h3>
  <?php endif; ?>

<?php endforeach; ?>